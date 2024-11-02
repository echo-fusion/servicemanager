<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Providers;

interface ServiceProviderInterface
{
    public function register(SettableContainerInterface $container): void;

    public function boot(SettableContainerInterface $container, array $config): void;
}
