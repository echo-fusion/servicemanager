<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Contract;

use Container\DependenciesRepositoryInterface;
use Psr\Container\ContainerInterface;

interface ServiceManagerInterface extends ContainerInterface
{
    public function bind(DependenciesRepositoryInterface $dependenciesRepository): void;

    /**
     * @param non-empty-string $id
     */
    public function resolve(string $id): mixed;

    public function getDependenciesManager(): DependenciesRepositoryInterface;
}
