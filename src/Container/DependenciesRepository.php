<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Container;

use EchoFusion\ServiceManager\ServiceManagerException;
use function sprintf;

final class DependenciesRepository implements DependenciesRepositoryInterface
{
    private array $factories = [];

    private array $invokables = [];

    private array $aliases = [];

    public function getType(string $id): string
    {
        if (isset($this->aliases[$id])) {
            return self::Alias;
        }

        if (isset($this->invokables[$id])) {
            return self::Invokable;
        }

        if (isset($this->factories[$id])) {
            return self::Factory;
        }

        throw new ServiceManagerException(sprintf('Given alias:%s does\'t exist in container to determine type!', $id));
    }

    public function has(string $id): bool
    {
        return isset($this->getDependencies()[$id]);
    }

    public function get(string $id)
    {
        $mergedDependencies = $this->getDependencies();
        if (!isset($mergedDependencies[$id])) {
            throw new ServiceManagerException(sprintf('Given alias:%s does\'t exist in container to get instance!', $id));
        }

        return $mergedDependencies[$id];
    }

    public function getDependencies(): array
    {
        return array_merge($this->factories, $this->invokables, $this->aliases);
    }

    public function getFactories(): array
    {
        return $this->factories;
    }

    public function setFactory(string $id, callable|string $concrete): void
    {
        $this->factories[$id] = $concrete;
    }

    public function getInvokables(): array
    {
        return $this->invokables;
    }

    public function setInvokable(string $id): void
    {
        $this->invokables[$id] = $id;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function setAlias(string $id, string $concrete): void
    {
        $this->aliases[$id] = $concrete;
    }
}
