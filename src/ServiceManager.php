<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager;

use Container\DependenciesRepositoryInterface;
use EchoFusion\ServiceManager\Contract\ServiceManagerInterface;
use EchoFusion\ServiceManager\Strategies\ContainerResolverStrategyInterface;

class ServiceManager implements ServiceManagerInterface
{
    public function __construct(
        private DependenciesRepositoryInterface $dependenciesRepository,
        private readonly ContainerResolverStrategyInterface $containerResolverStrategy,
        private readonly bool $allowOverride,
    ) {
        $this->bind($dependenciesRepository);
    }

    public function get(string $id): mixed
    {
        if ($this->has($id)) {
            $entry = $this->dependenciesRepository->get($id);
            if (is_callable($entry)) {
                return ($entry($this));
            }
        }

        return $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return $this->dependenciesRepository->has($id);
    }

    public function getDependenciesManager(): DependenciesRepositoryInterface
    {
        return $this->dependenciesRepository;
    }

    public function bind(DependenciesRepositoryInterface $dependenciesRepository): void
    {
        $this->dependenciesRepository = $dependenciesRepository;

        foreach ($dependenciesRepository->getDependencies() as $id => $entry) {
            if ($this->has($id) && !$this->allowOverride) {
                throw new ServiceManagerException(sprintf('Dependency "%s" is already exist in container!', $id));
            }

            match ($dependenciesRepository->getType($id)) {
                DependenciesRepositoryInterface::Alias => $this->dependenciesRepository->setAlias($id, $entry),
                DependenciesRepositoryInterface::Invokable => $this->dependenciesRepository->setInvokable($entry),
                DependenciesRepositoryInterface::Factory => $this->dependenciesRepository->setFactory($id, $entry),
            };
        }
    }

    public function resolve(string $id): mixed
    {
        return $this->containerResolverStrategy->resolve($id, $this);
    }
}
