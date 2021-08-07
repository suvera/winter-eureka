<?php
declare(strict_types=1);

namespace dev\winterframework\eureka\consul;

use DCarbone\PHPConsulAPI\Catalog\CatalogDeregistration;
use DCarbone\PHPConsulAPI\Catalog\CatalogNodeResponse;
use DCarbone\PHPConsulAPI\Catalog\CatalogNodeServicesListResponse;
use DCarbone\PHPConsulAPI\Catalog\CatalogRegistration;
use DCarbone\PHPConsulAPI\Catalog\CatalogServicesResponse;
use DCarbone\PHPConsulAPI\Catalog\GatewayServicesResponse;
use DCarbone\PHPConsulAPI\Catalog\NodesResponse;
use DCarbone\PHPConsulAPI\QueryOptions;
use DCarbone\PHPConsulAPI\ValuedQueryStringsResponse;
use DCarbone\PHPConsulAPI\ValuedStringsResponse;
use DCarbone\PHPConsulAPI\WriteOptions;

/**
 * Read Consul API documentation: https://www.consul.io/api-docs/catalog
 */
interface DiscoveryClient {

    /**
     * This endpoint is a low-level mechanism for registering or updating entries in the catalog
     *     +----------------+------------------------------------+
     *     |  HTTP Request  |   Endpoint                         |
     *     +----------------+------------------------------------+
     *     |   PUT          |  /catalog/register                 |
     *     +-----------------------------------------------------+
     * More info: https://www.consul.io/api-docs/catalog
     */
    public function register(CatalogRegistration $service, ?WriteOptions $opts = null): bool;

    /**
     * This endpoint is a low-level mechanism for directly removing entries from the Catalog
     *     +----------------+------------------------------------+
     *     |  HTTP Request  |   Endpoint                         |
     *     +----------------+------------------------------------+
     *     |   PUT          |  /catalog/deregister               |
     *     +-----------------------------------------------------+
     */
    public function deregister(CatalogDeregistration $service, ?WriteOptions $opts = null): bool;

    /**
     * This endpoint returns the list of all known datacenters
     *     +----------------+------------------------------------+
     *     |  HTTP Request  |   Endpoint                         |
     *     +----------------+------------------------------------+
     *     |   GET          |  /catalog/datacenters              |
     *     +-----------------------------------------------------+
     */
    public function getDataCenters(): ValuedStringsResponse;

    /**
     * This endpoint and returns the nodes registered in a given datacenter.
     *     +----------------+------------------------------------+
     *     |  HTTP Request  |   Endpoint                         |
     *     +----------------+------------------------------------+
     *     |   GET          |  /catalog/nodew                    |
     *     +-----------------------------------------------------+
     */
    public function getNodes(?QueryOptions $opts = null): NodesResponse;

    /**
     * This endpoint returns the services registered in a given datacenter.
     *     +----------------+------------------------------------+
     *     |  HTTP Request  |   Endpoint                         |
     *     +----------------+------------------------------------+
     *     |   GET          |  /catalog/services                 |
     *     +-----------------------------------------------------+
     */
    public function getServices(?QueryOptions $opts = null): ValuedQueryStringsResponse;

    /**
     * This endpoint returns the node's registered services.
     *     +----------------+------------------------------------+
     *     |  HTTP Request  |   Endpoint                         |
     *     +----------------+------------------------------------+
     *     |   GET          |  /catalog/node-services/:node      |
     *     +-----------------------------------------------------+
     */
    public function getServicesForNode(string $node, ?QueryOptions $opts = null): CatalogNodeServicesListResponse;

    /**
     * This endpoint returns the nodes providing a service in a given datacenter.
     *     +----------------+------------------------------------+
     *     |  HTTP Request  |   Endpoint                         |
     *     +----------------+------------------------------------+
     *     |   GET          |  /catalog/service/:service            |
     *     +-----------------------------------------------------+
     */
    public function getNodesForService(
        string $service,
        array $tags = [],
        ?QueryOptions $opts = null
    ): CatalogServicesResponse;

    /**
     * This endpoint returns the node's registered services by Service ID
     *     +----------------+------------------------------------+
     *     |  HTTP Request  |   Endpoint                         |
     *     +----------------+------------------------------------+
     *     |   GET          |  /catalog/node/:node              |
     *     +-----------------------------------------------------+
     */
    public function getServicesForNodeById(string $node, ?QueryOptions $opts = null): CatalogNodeResponse;

    /**
     * This endpoint returns the services associated with an ingress gateway or terminating gateway.
     *     +----------------+--------------------------------------+
     *     |  HTTP Request  |   Endpoint                           |
     *     +----------------+--------------------------------------+
     *     |   GET          |  /catalog/gateway-services/:gateway  |
     *     +-------------------------------------------------------+
     */
    public function getGatewayServices(string $gateway, ?QueryOptions $opts = null): GatewayServicesResponse;

}