<?php
declare(strict_types=1);

namespace dev\winterframework\eureka\netflix;

use dev\winterframework\type\ArrayedObject;
use JsonSerializable;

class ServiceInstance extends ArrayedObject implements JsonSerializable {
    protected string $instanceId;
    private string $hostName;
    private string $app;
    private string $ipAddr;
    /**
     * one of ['UP', 'DOWN', 'STARTING', 'OUT_OF_SERVICE', 'UNKNOWN']
     */
    private string $status = 'UP';
    private string $overriddenstatus = 'UNKNOWN';
    /**
     * @var array{'$' => 8080, '@enabled' => true}
     */
    private array $port;
    /**
     * @var array{'$' => 443, '@enabled' => false}
     */
    private array $securePort;
    private string|int $countryId = 1;
    /**
     * Name can be ony one of ['Amazon' , 'MyOwn']
     */
    private array $dataCenterInfo = [
        '@class' => 'com.netflix.appinfo.InstanceInfo$DefaultDataCenterInfo',
        'name' => 'MyOwn'
    ];
    private string $homePageUrl = '';
    private string $statusPageUrl = '';
    private string $healthCheckUrl = '';
    private string $vipAddress = '';
    private string $secureVipAddress = '';
    private array $metadata = [
        '@class' => 'java.util.Collections$EmptyMap'
    ];
    /**
     * @var array{'evictionDurationInSecs' => 90}
     */
    private array $leaseInfo = [
        'renewalIntervalInSecs' => 30,
        'durationInSecs' => 90
    ];

    public function getInstanceId(): string {
        return $this->instanceId;
    }

    public function setInstanceId(string $instanceId): void {
        $this->instanceId = $instanceId;
    }

    public function getHostName(): string {
        return $this->hostName ?? '';
    }

    public function setHostName(string $hostName): void {
        $this->hostName = $hostName;
    }

    public function getApp(): string {
        return $this->app;
    }

    public function setApp(string $app): void {
        $this->app = $app;
    }

    public function getIpAddr(): string {
        return $this->ipAddr ?? '';
    }

    public function setIpAddr(string $ipAddr): void {
        $this->ipAddr = $ipAddr;
    }

    public function getStatus(): string {
        return $this->status ?? '';
    }

    public function setStatus(string $status): void {
        $this->status = $status;
    }

    public function getOverriddenstatus(): string {
        return $this->overriddenstatus ?? '';
    }

    public function setOverriddenstatus(string $overriddenstatus): void {
        $this->overriddenstatus = $overriddenstatus;
    }

    public function getPort(): array {
        return $this->port ?? [];
    }

    public function setPort(int|string|array $port, bool $enabled = true): void {
        $this->port = is_array($port) ? $port : ['$' => $port, '@enabled' => $enabled];
    }

    public function getSecurePort(): array {
        return $this->securePort ?? [];
    }

    public function setSecurePort(int|string|array $securePort, bool $enabled = true): void {
        $this->securePort = is_array($securePort) ? $securePort : ['$' => $securePort, '@enabled' => $enabled];
    }

    public function getCountryId(): string|int {
        return $this->countryId ?? '';
    }

    public function setCountryId(string|int $countryId): void {
        $this->countryId = $countryId;
    }

    public function getDataCenterInfo(): array {
        return $this->dataCenterInfo ?? [];
    }

    public function setDataCenterInfo(array $dataCenterInfo): void {
        $this->dataCenterInfo = $dataCenterInfo;
    }

    public function getHomePageUrl(): string {
        return $this->homePageUrl ?? '';
    }

    public function setHomePageUrl(string $homePageUrl): void {
        $this->homePageUrl = $homePageUrl;
    }

    public function getStatusPageUrl(): string {
        return $this->statusPageUrl ?? '';
    }

    public function setStatusPageUrl(string $statusPageUrl): void {
        $this->statusPageUrl = $statusPageUrl;
    }

    public function getHealthCheckUrl(): string {
        return $this->healthCheckUrl ?? '';
    }

    public function setHealthCheckUrl(string $healthCheckUrl): void {
        $this->healthCheckUrl = $healthCheckUrl;
    }

    public function getVipAddress(): string {
        return $this->vipAddress ?? '';
    }

    public function setVipAddress(string $vipAddress): void {
        $this->vipAddress = $vipAddress;
    }

    public function getSecureVipAddress(): string {
        return $this->secureVipAddress ?? '';
    }

    public function setSecureVipAddress(string $secureVipAddress): void {
        $this->secureVipAddress = $secureVipAddress;
    }

    public function getMetadata(): array {
        return $this->metadata ?? [];
    }

    public function setMetadata(array $metadata): void {
        $this->metadata = $metadata;
    }

    public function addMetadata(string $metaKey, mixed $metaVal): void {
        if (!isset($this->metadata)) {
            $this->metadata = [];
        }
        $this->metadata[$metaKey] = $metaVal;
    }

    public function getLeaseInfo(): array {
        return $this->leaseInfo ?? [];
    }

    public function setLeaseInfo(array $leaseInfo): void {
        $this->leaseInfo = $leaseInfo;
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }

    public function getId(): string {
        return $this->getApp() . '->' . $this->getInstanceId();
    }

}
