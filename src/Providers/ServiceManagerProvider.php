<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Providers;

use EchoFusion\ServiceManager\ServiceManager;
use EchoFusion\ServiceManager\ServiceManagerInterface;
use function is_array;

class ServiceManagerProvider implements ServiceProviderInterface
{
    /**
     * Lazy loading: Service is instantiated only when needed
     */
    public function register(SettableContainerInterface $container): void
    {
        $container->set(ServiceManagerInterface::class, function () {
            return new ServiceManager();
        });
    }

    /**
     * Boot the services by given config
     *
     * @param list<class-string> $config
     */
    public function boot(SettableContainerInterface $container, array $config): void
    {
        $serviceManager = $container->get(ServiceManagerInterface::class);

        if (isset($config['allow_override'])) {
            $serviceManager->enableOverride($config['allow_override']);
        }

        if (isset($config['dependencies']) && is_array($config['dependencies'])) {
            foreach ($config['dependencies'] as $id => $dependency) {
                $serviceManager->set($id, $dependency);
            }
        }

        // persist changes on container
        $serviceManager->set(ServiceManagerInterface::class, fn () => $serviceManager);
    }
}
