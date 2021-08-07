# WinterBoot Module - Eureka

Winter Eureka is a module that provides easy configuration and access to Service Discovery functionality from WinterBoot applications.

### Service Discovery tools:

- [Consul.io](https://www.consul.io/use-cases/service-discovery-and-health-checking)
- [Netflix Eureka](https://github.com/Netflix/eureka)

## Setup

1. This requires `swoole` php extension

```shell
composer require suvera/winter-eureka
```

To enable Eureka module in applications, append following code to **application.yml**

```yaml

modules:
    - module: dev\winterframework\eureka\EurekaModule
      enabled: true
      configFile: eureka-config.yml

```

**configFile** is a file path (relative to config dir or absolute path)


## eureka-config.yml

```yaml
# Consul.io
consul:
    -   name: consulBean01
        serviceUrl: http://127.0.0.1:8500
        authType: Basic
        credentials:
        credentialFile:
        dataCenter:
        waitTimeSecs:
        consulToken:
        consulTokenFile:
        ignoreSsl:
        caFile:
        certFile:
        keyFile:


# Netflix Eureka
eureka:
    -   name: netflixBean01
        serviceUrl: http://localhost:8761/eureka
        authType: Basic
        credentials:
        credentialFile:
        ignoreSsl:

# End
```

Service/Client beans can be Autowired.

```phpt

#[Autowired('consulBean01')]
protected DiscoveryClient $discoveryClient;


#[Autowired('netflixBean01')]
protected EurekaClient $eurekaClient;
```