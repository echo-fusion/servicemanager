<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Strategies;

use EchoFusion\ServiceManager\DependenciesRepositoryInterface;
use EchoFusion\ServiceManager\ServiceManagerInterface;

class ManualStrategy implements ContainerResolverStrategyInterface
{
    public function resolve(string $id, ServiceManagerInterface $serviceManager): object
    {
        $dependenciesRepository = $serviceManager->getDependenciesManager();
        $dependencyType = $dependenciesRepository->getType($id);

        return match ($dependencyType) {
            DependenciesRepositoryInterface::Alias => $this->getAliasInstance($id, $serviceManager),
            DependenciesRepositoryInterface::Invokable => $this->getInvokableInstance($id, $serviceManager),
            DependenciesRepositoryInterface::Factory => $this->getFactoryInstance($id, $serviceManager),
        };
    }

    public function getAliasInstance(string $id, ServiceManagerInterface $serviceManager): object
    {
        $dependenciesRepository = $serviceManager->getDependenciesManager();
        $instance = $dependenciesRepository->get($id);

        return $this->resolve($instance, $serviceManager);
    }

    public function getInvokableInstance(string $id, ServiceManagerInterface $serviceManager): object
    {
        $dependenciesRepository = $serviceManager->getDependenciesManager();
        $instance = $dependenciesRepository->get($id);

        return new $instance();
    }

    public function getFactoryInstance(string $id, ServiceManagerInterface $serviceManager): object
    {
        $dependenciesRepository = $serviceManager->getDependenciesManager();
        $class = new ($dependenciesRepository->get($id));

        return $class($serviceManager);
    }
}
