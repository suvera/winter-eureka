<?php
declare(strict_types=1);

namespace dev\winterframework\eureka\netflix;

use dev\winterframework\core\context\ApplicationContext;

class EurekaClientFactory {

    public function __construct(
        protected ApplicationContext $ctx,
        protected array $config
    ) {
    }

    public function getClient(string $beanName): EurekaClient {
        return $this->ctx->beanByNameClass($beanName, EurekaClient::class);
    }

    public function getDefaultClient(): EurekaClient {
        return $this->ctx->beanByClass(EurekaClient::class);
    }

    /**
     * @return EurekaClient[]
     */
    public function getAllClients(): array {
        $arr = [];
        foreach ($this->config as $item) {
            $arr[] = $this->getClient($item['name']);
        }
        return $arr;
    }
}