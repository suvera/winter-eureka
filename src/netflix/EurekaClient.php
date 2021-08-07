<?php
declare(strict_types=1);

namespace dev\winterframework\eureka\netflix;

interface EurekaClient {

    public function register(ServiceInstance $service): bool;

    public function heartbeat(ServiceInstance $service): bool;

    public function deregister(ServiceInstance $service): bool;

    public function deregister2(string $appName, string $instanceId): bool;

    public function isRegistered(ServiceInstance $service): bool;

    public function isRegistered2(string $appName, string $instanceId): bool;

    /**
     * @return ServiceInstance[]
     */
    public function findService(string $appName): array;

    public function findServiceInstance(string $appName, string $instanceId): ?ServiceInstance;
}