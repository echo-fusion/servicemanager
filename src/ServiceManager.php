<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager;

use EchoFusion\Contracts\ServiceManager\ServiceManagerInterface;
use EchoFusion\ServiceManager\Exceptions\ServiceManagerException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use function is_callable;
use function sprintf;

class ServiceManager implements ServiceManagerInterface
{
    // Cache for resolved services (shared instances)
    protected array $services = [];

    // Definitions to resolve services (class names or callables)
    protected array $definitions = [];

    // Flags to indicate whether a service is shared (singleton)
    protected array $sharedServices = [];

    private bool $allowOverride = false;

    /**
     * Registers a service in the container.
     *
     * @param non-empty-string $id Identifier for the service
     * @param callable|class-string $service A callable or class name for the service
     * @param bool $shared Whether the service should be shared (singleton)
     * @throws ServiceManagerException
     */
    public function set(string $id, callable|string $service, bool $shared = false): void
    {
        if ($this->has($id) && !$this->allowOverride) {
            throw new ServiceManagerException(sprintf('Service "%s" already exists in the container!', $id));
        }

        // Store the definition and shared status
        $this->definitions[$id] = $service;
        $this->sharedServices[$id] = $shared;
    }

    public function enableOverride(bool $enable): void
    {
        $this->allowOverride = $enable;
    }

    public function getAllowOverride(): bool
    {
        return $this->allowOverride;
    }

    /**
     * Returns a service by its identifier.
     *
     * @param string $id The service identifier
     * @return mixed The resolved service
     * @throws ServiceManagerException|ReflectionException
     */
    public function get(string $id): mixed
    {
        // Check if the service is already resolved and cached
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        // Check if the service is defined
        if ($this->has($id)) {
            $definition = $this->definitions[$id];

            // If the service is not a callable, assume it's a class name and resolve it
            if (!is_callable($definition)) {
                return $this->resolve($definition);
            }

            // Resolve the service via the callable (factory)
            $service = $definition($this);

            // Cache the service if it's shared (singleton)
            if ($this->sharedServices[$id]) {
                $this->services[$id] = $service;
            }

            return $service;
        }

        // If no definition, try to auto-wire the class
        return $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    /**
     * Resolves a class by auto-wiring its dependencies.
     *
     * @param string $id The class name to resolve
     * @return mixed The resolved instance
     * @throws ReflectionException
     */
    public function resolve(string $id): mixed
    {
        $reflectionClass = new ReflectionClass($id);
        if (!$reflectionClass->isInstantiable()) {
            throw new ServiceManagerException('Class ' . $id . ' is not instantiable!');
        }

        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            return new $id();
        }

        $parameters = $constructor->getParameters();
        if (!$parameters) {
            return new $id();
        }

        // Resolve constructor dependencies
        $dependencies = array_map(function (ReflectionParameter $param) use ($id) {
            $name = $param->getName();
            $type = $param->getType();

            if (!$type) {
                throw new ServiceManagerException(
                    'Failed to resolve the class ' . $id . ' because param ' . $name . ' is missing a type hint'
                );
            }

            if ($type instanceof ReflectionUnionType) {
                throw new ServiceManagerException(
                    'Failed to resolve the class ' . $id . ' because of union type of param ' . $name
                );
            }

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                return $this->get($type->getName());
            }

            throw new ServiceManagerException('Failed to resolve the class ' . $id . ' because of invalid param ' . $name);
        }, $parameters);

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}
