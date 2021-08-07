<?php
declare(strict_types=1);

namespace dev\winterframework\eureka\netflix;

use dev\winterframework\core\context\ApplicationContext;
use dev\winterframework\eureka\exception\EurekaException;
use dev\winterframework\reflection\ReflectionUtil;
use dev\winterframework\type\Arrays;
use dev\winterframework\util\log\Wlf4p;
use GuzzleHttp\Client;
use Swoole\Timer;
use Throwable;

class EurekaClientImpl implements EurekaClient {
    use Wlf4p;

    /**
     * @var ServiceInstance[]
     */
    protected array $registered = [];
    protected Client $client;
    private bool $timerStarted = false;

    public function __construct(
        protected ApplicationContext $ctx,
        protected array $config
    ) {
        ReflectionUtil::assertPhpExtension('swoole');

        Arrays::assertKey($this->config, 'serviceUrl', 'Eureka module config error');
        $ignoreSsl = $this->config['ignoreSsl'] ?? false;
        $timeout = isset($this->config['timeout']) ? intval($this->config['timeout']) : 0;

        $options = [
            'base_uri' => $this->config['serviceUrl'],
            'verify' => boolval($ignoreSsl)
        ];
        if ($timeout > 0) {
            $options['timeout'] = $timeout;
        }
        $options['headers'] = [];
        $authType = $this->config['authType'] ?? 'Basic';
        $cred = null;
        if (isset($this->config['credentials'])) {
            $cred = $this->config['credentials'];
        } else if (isset($this->config['credentialFile'])) {
            if (!file_exists($this->config['credentialFile'])) {
                throw new EurekaException('Could not find file "credentialFile"');
            }
            $cred = file_get_contents($this->config['credentialFile']);
            $cred = trim($cred);
        }
        if ($cred) {
            $options['headers']['Authorization'] = match ($authType) {
                'Basic' => $authType . ' ' . base64_encode($cred),
                default => $authType . ' ' . str_replace("\n", ' ', $cred),
            };
        }

        $this->client = new Client($options);
    }

    public function register(ServiceInstance $service): bool {
        self::logInfo('Register service instance ... ' . $service->getId());

        $config = ['instance' => $service->jsonSerialize()];
        try {
            $response = $this->client->request(
                'POST',
                '/eureka/v2/apps/' . $service->getApp(),
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                    'body' => json_encode($config)
                ],
            );

            if ($response->getStatusCode() != 204) {
                self::logInfo('Could not register with Eureka ... (code: ' . $response->getStatusCode() . ')');
            }
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }

        if (!$this->timerStarted) {
            $this->timerStarted = true;
            // Non-blocking code is recommended
            Timer::tick(90000, function () {
                go(function () {
                    $this->checkHeartbeat();
                });
            });
        }

        return true;
    }

    protected function checkHeartbeat(): void {
        foreach ($this->registered as $service) {
            try {
                $this->heartbeat($service);
            } catch (Throwable $e) {
                self::logEx($e, 'Heartbeat failed with exception (code: ' . $e->getCode() . ')');
            }
        }
    }

    public function heartbeat(ServiceInstance $service): bool {
        self::logInfo('Sending heartbeat ... ' . $service->getId());

        try {
            $response = $this->client->request(
                'PUT',
                '/eureka/v2/apps/' . $service->getApp() . '/' . $service->getInstanceId(),
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );

            if ($response->getStatusCode() != 200) {
                self::logInfo('Heartbeat failed... (code: ' . $response->getStatusCode() . ')');
            }
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }
        return true;
    }

    public function deregister(ServiceInstance $service): bool {
        return $this->deregister2($service->getApp(), $service->getInstanceId());
    }

    /**
     * @throws
     */
    public function deregister2(string $appName, string $instanceId): bool {
        self::logInfo('De-registering service ... ' . $appName . '->' . $instanceId);

        $response = $this->client->request(
            'DELETE',
            '/eureka/v2/apps/' . $appName . '/' . $instanceId,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]
        );

        if ($response->getStatusCode() != 200) {
            throw new EurekaException('Cloud not de-register from Eureka ' . $appName . '->' . $instanceId);
        }
        return true;
    }

    public function isRegistered(ServiceInstance $service): bool {
        return $this->isRegistered2($service->getApp(), $service->getInstanceId());
    }

    public function isRegistered2(string $appName, string $instanceId): bool {
        try {
            $response = $this->client->request(
                'GET',
                '/eureka/v2/apps/' . $appName . '/' . $instanceId,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );
            $statusCode = $response->getStatusCode();
        } catch (Throwable $ex) {
            self::logEx($ex);
            return false;
        }

        return $statusCode == 200;
    }

    /**
     * @return ServiceInstance[]
     */
    public function findService(string $appName): array {
        $instances = [];
        try {
            $response = $this->client->request(
                'GET',
                '/eureka/v2/apps/' . $appName,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );
            if ($response->getStatusCode() != 200) {
                self::logError("Could not get instances from Eureka for '" . $appName . "'. ");
                return [];
            }

            $body = json_decode($response->getBody()->getContents(), true);
            if (!isset($body['application']['instance'])) {
                self::logError("No instance found for '" . $appName . "'. ");
                return [];
            }

            $instances = $body['application']['instance'];
        } catch (Throwable $e) {
            self::logError("No instance found for '" . $appName . "'. " . $e->getMessage());
        }

        $arr = [];
        foreach ($instances as $instance) {
            $arr[] = new ServiceInstance($instance);
        }

        return $arr;
    }

    public function findServiceInstance(string $appName, string $instanceId): ?ServiceInstance {
        $instance = null;
        try {
            $response = $this->client->request(
                'GET',
                '/eureka/v2/apps/' . $appName . '/' . $instanceId,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );

            if ($response->getStatusCode() != 200) {
                self::logError("Could not get instances from Eureka for '" . $appName . "/$instanceId'.");
                return null;
            }

            $body = json_decode($response->getBody()->getContents(), true);

            if (!isset($body['instance'])) {
                self::logError("No instance found for '" . $appName . "/$instanceId'.");
                return null;
            }

            $instance = $body['instance'];
        } catch (Throwable $e) {
            self::logError("No instance found for '" . $appName . "/$instanceId'. " . $e->getMessage());
        }

        if ($instance) {
            return new ServiceInstance($instance);
        }

        return null;
    }
}
