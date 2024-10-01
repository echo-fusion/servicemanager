<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Strategies;

use EchoFusion\ServiceManager\ServiceManagerInterface;

interface ContainerResolverStrategyInterface
{
    public function resolve(string $id, ServiceManagerInterface $serviceManager): object;
}
