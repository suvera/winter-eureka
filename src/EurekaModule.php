<?php
declare(strict_types=1);

namespace dev\winterframework\eureka;

use dev\winterframework\core\app\WinterModule;
use dev\winterframework\core\context\ApplicationContext;
use dev\winterframework\core\context\ApplicationContextData;
use dev\winterframework\core\context\WinterBeanProviderContext;
use dev\winterframework\eureka\consul\DiscoveryClient;
use dev\winterframework\eureka\consul\DiscoveryClientFactory;
use dev\winterframework\eureka\consul\DiscoveryClientImpl;
use dev\winterframework\eureka\netflix\EurekaClient;
use dev\winterframework\eureka\netflix\EurekaClientFactory;
use dev\winterframework\eureka\netflix\EurekaClientImpl;
use dev\winterframework\exception\BeansException;
use dev\winterframework\reflection\ReflectionUtil;
use dev\winterframework\stereotype\Module;
use dev\winterframework\util\log\Wlf4p;
use dev\winterframework\util\ModuleTrait;

#[Module]
class EurekaModule implements WinterModule {
    use Wlf4p;
    use ModuleTrait;

    public function init(ApplicationContext $ctx, ApplicationContextData $ctxData): void {
        ReflectionUtil::assertPhpExtension('swoole');
    }

    public function begin(ApplicationContext $ctx, ApplicationContextData $ctxData): void {
        $moduleDef = $ctx->getModule(static::class);
        $config = $this->retrieveConfiguration($ctx, $ctxData, $moduleDef);

        if (isset($config['consul']) && is_array($config['consul'])) {
            $this->buildConsulDiscovery($config, $ctx, $ctxData);
        }

        if (isset($config['eureka']) && is_array($config['eureka'])) {
            $this->buildNetflixEureka($config, $ctx, $ctxData);
        }
    }

    protected function buildConsulDiscovery(
        array $config,
        ApplicationContext $ctx,
        ApplicationContextData $ctxData
    ): void {
        /** @var WinterBeanProviderContext $beanProvider */
        $beanProvider = $ctxData->getBeanProvider();
        $services = $config['consul'];

        foreach ($services as $id => $service) {
            if (!isset($service['name'])) {
                $service['name'] = 'eureka-consul-' . ($id + 1);
                $services[$id]['name'] = $service['name'];
            }

            if ($ctx->hasBeanByName($service['name'])) {
                throw new BeansException("Bean already exist with name '" . $service['name']
                    . "' Eureka Consul bean name conflicts with other bean");
            }

            $tpl = new DiscoveryClientImpl($ctx, $service);
            $beanProvider->registerInternalBean(
                $tpl,
                DiscoveryClient::class,
                false,
                $service['name'],
                true
            );
        }

        $factory = new DiscoveryClientFactory($ctx, $services);
        $beanProvider->registerInternalBean(
            $factory,
            DiscoveryClientFactory::class
        );
    }

    protected function buildNetflixEureka(
        array $config,
        ApplicationContext $ctx,
        ApplicationContextData $ctxData
    ): void {
        /** @var WinterBeanProviderContext $beanProvider */
        $beanProvider = $ctxData->getBeanProvider();
        $services = $config['eureka'];

        foreach ($services as $id => $service) {
            if (!isset($service['name'])) {
                $service['name'] = 'eureka-netflix-' . ($id + 1);
                $services[$id]['name'] = $service['name'];
            }

            if ($ctx->hasBeanByName($service['name'])) {
                throw new BeansException("Bean already exist with name '" . $service['name']
                    . "' Eureka Netflix bean name conflicts with other bean");
            }

            $tpl = new EurekaClientImpl($ctx, $service);
            $beanProvider->registerInternalBean(
                $tpl,
                EurekaClient::class,
                false,
                $service['name'],
                true
            );
        }

        $factory = new EurekaClientFactory($ctx, $services);
        $beanProvider->registerInternalBean(
            $factory,
            EurekaClientFactory::class
        );
    }

}