<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Strategies;

use EchoFusion\ServiceManager\Contract\ServiceManagerInterface;

interface ContainerResolverStrategyInterface
{
    public function resolve(string $id, ServiceManagerInterface $serviceManager): mixed;
}
