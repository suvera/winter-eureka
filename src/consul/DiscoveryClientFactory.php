<?php
declare(strict_types=1);

namespace dev\winterframework\eureka\consul;

use dev\winterframework\core\context\ApplicationContext;

class DiscoveryClientFactory {

    public function __construct(
        protected ApplicationContext $ctx,
        protected array $config
    ) {
    }

    public function getClient(string $beanName): DiscoveryClient {
        return $this->ctx->beanByNameClass($beanName, DiscoveryClient::class);
    }

    public function getDefaultClient(): DiscoveryClient {
        return $this->ctx->beanByClass(DiscoveryClient::class);
    }

    /**
     * @return DiscoveryClient[]
     */
    public function getAllClients(): array {
        $arr = [];
        foreach ($this->config as $item) {
            $arr[] = $this->getClient($item['name']);
        }
        return $arr;
    }
}