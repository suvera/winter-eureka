<?php
declare(strict_types=1);

namespace dev\winterframework\eureka\consul;

use DCarbone\PHPConsulAPI\Catalog\CatalogClient;
use DCarbone\PHPConsulAPI\Catalog\CatalogDeregistration;
use DCarbone\PHPConsulAPI\Catalog\CatalogNodeResponse;
use DCarbone\PHPConsulAPI\Catalog\CatalogNodeServicesListResponse;
use DCarbone\PHPConsulAPI\Catalog\CatalogRegistration;
use DCarbone\PHPConsulAPI\Catalog\CatalogServicesResponse;
use DCarbone\PHPConsulAPI\Catalog\GatewayServicesResponse;
use DCarbone\PHPConsulAPI\Catalog\NodesResponse;
use DCarbone\PHPConsulAPI\Config;
use DCarbone\PHPConsulAPI\QueryOptions;
use DCarbone\PHPConsulAPI\ValuedQueryStringsResponse;
use DCarbone\PHPConsulAPI\ValuedStringsResponse;
use DCarbone\PHPConsulAPI\WriteOptions;
use dev\winterframework\core\context\ApplicationContext;
use dev\winterframework\eureka\exception\EurekaException;
use dev\winterframework\reflection\ReflectionUtil;
use dev\winterframework\type\Arrays;
use dev\winterframework\util\log\Wlf4p;
use GuzzleHttp\Client;
use Throwable;

class DiscoveryClientImpl implements DiscoveryClient {
    use Wlf4p;

    protected CatalogClient $client;
    protected Client $httpClient;
    private bool $timerStarted = false;

    public function __construct(
        protected ApplicationContext $ctx,
        protected array $config
    ) {
        ReflectionUtil::assertPhpExtension('swoole');

        Arrays::assertKey($this->config, 'serviceUrl', 'Eureka module config error');
        $ignoreSsl = $this->config['ignoreSsl'] ?? false;
        $timeout = isset($this->config['timeout']) ? intval($this->config['timeout']) : 0;

        $httpOptions = [
            'verify' => boolval($ignoreSsl)
        ];
        if ($timeout > 0) {
            $httpOptions['timeout'] = $timeout;
        }
        $httpOptions['headers'] = [];
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
            $httpOptions['headers']['Authorization'] = match ($authType) {
                'Basic' => $authType . ' ' . base64_encode($cred),
                default => $authType . ' ' . str_replace("\n", ' ', $cred),
            };
        }

        $this->httpClient = new Client($httpOptions);

        $urlParts = parse_url($this->config['serviceUrl']);

        $address = $urlParts['host'] . ':' . $urlParts['port'];
        $options = [
            'HttpClient' => $this->httpClient,
            'Address' => $address,
            'Scheme' => $urlParts['scheme'] ?? 'http',
            'InsecureSkipVerify' => $ignoreSsl,
        ];
        if (isset($this->config['dataCenter'])) {
            $options['Datacenter'] = $this->config['dataCenter'];
        }
        if (isset($this->config['waitTimeSecs'])) {
            $options['WaitTime'] = $this->config['waitTimeSecs'] . 's';
        }
        if (isset($this->config['consulToken'])) {
            $options['Token'] = $this->config['consulToken'];
        }
        if (isset($this->config['consulTokenFile'])) {
            $options['TokenFile'] = $this->config['consulTokenFile'];
        }
        if (isset($this->config['caFile'])) {
            $options['CAFile'] = $this->config['caFile'];
        }
        if (isset($this->config['certFile'])) {
            $options['CertFile'] = $this->config['certFile'];
        }
        if (isset($this->config['keyFile'])) {
            $options['KeyFile'] = $this->config['keyFile'];
        }

        $catalog = new Config($options);
        $this->client = new CatalogClient($catalog);
    }

    public function register(CatalogRegistration $service, ?WriteOptions $opts = null): bool {
        self::logInfo('Register service instance ... ' . $service->getId() . ' ' . $service->getNode());
        try {
            $response = $this->client->Register($service, $opts);
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }

        if ($response->getErr()) {
            self::logError('Could not register with Consul ... (code: '
                . $response->getErr()->getMessage() . ')');
            throw new EurekaException($response->getErr()->getMessage());
        }
        return true;
    }

    public function deregister(CatalogDeregistration $service, ?WriteOptions $opts = null): bool {
        self::logInfo('DeRegister service instance ... ' . $service->getServiceID());
        try {
            $this->client->Deregister($service, $opts);
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }
        return true;
    }

    public function getDataCenters(): ValuedStringsResponse {
        try {
            return $this->client->Datacenters();
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }
    }

    public function getNodes(?QueryOptions $opts = null): NodesResponse {
        try {
            return $this->client->Nodes($opts);
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }
    }

    public function getServices(?QueryOptions $opts = null): ValuedQueryStringsResponse {
        try {
            return $this->client->Services($opts);
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }
    }

    public function getServicesForNode(string $node, ?QueryOptions $opts = null): CatalogNodeServicesListResponse {
        try {
            return $this->client->NodeServicesList($node, $opts);
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }
    }

    public function getNodesForService(
        string $service,
        array $tags = [],
        ?QueryOptions $opts = null
    ): CatalogServicesResponse {
        try {
            return $this->client->ServiceMultipleTags($service, $tags, $opts);
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }
    }

    public function getServicesForNodeById(string $node, ?QueryOptions $opts = null): CatalogNodeResponse {
        try {
            return $this->client->Node($node, $opts);
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }
    }

    public function getGatewayServices(string $gateway, ?QueryOptions $opts = null): GatewayServicesResponse {
        try {
            return $this->client->GatewayServices($gateway, $opts);
        } catch (Throwable $e) {
            throw new EurekaException($e->getMessage(), 0, $e);
        }
    }

}