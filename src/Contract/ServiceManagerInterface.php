<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Contract;

use Psr\Container\ContainerInterface;
use EchoFusion\ServiceManager\Container\DependenciesRepositoryInterface;

interface ServiceManagerInterface extends ContainerInterface
{
    public function bind(DependenciesRepositoryInterface $dependenciesRepository): void;

    /**
     * @param non-empty-string $id
     */
    public function resolve(string $id): mixed;

    public function getDependenciesManager(): DependenciesRepositoryInterface;
}
