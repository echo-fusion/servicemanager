<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager;

use EchoFusion\Contracts\ServiceManager\ServiceManagerInterface;
use EchoFusion\ServiceManager\Exceptions\CircularDependencyException;
use EchoFusion\ServiceManager\Exceptions\NotFoundException;
use EchoFusion\ServiceManager\Exceptions\ServiceManagerException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use function array_key_exists;
use function array_pop;
use function class_exists;
use function implode;
use function in_array;
use function is_callable;
use function is_string;
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
     * Ids that are being resolved right now, used to detect circular dependencies.
     *
     * @var list<string>
     */
    private array $resolving = [];

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
        if (isset($this->definitions[$id]) && !$this->allowOverride) {
            throw new ServiceManagerException(sprintf('Service "%s" already exists in the container!', $id));
        }

        // Store the definition and shared status; a shared instance built from the old definition must not survive
        $this->definitions[$id] = $service;
        $this->sharedServices[$id] = $shared;
        unset($this->services[$id]);
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
     * A defined service is built from its definition (and kept when it is shared). An id without a
     * definition that names an instantiable class is auto-wired and returned as a new instance each time.
     *
     * @param string $id The service identifier
     * @return mixed The resolved service
     * @throws NotFoundException when nothing is defined and the id is not an instantiable class
     * @throws CircularDependencyException when services depend on each other in a loop
     * @throws ServiceManagerException
     */
    public function get(string $id): mixed
    {
        // Already built and shared (array_key_exists, so a shared service that is null is still cached)
        if (array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('Service "%s" is not defined and is not an instantiable class.', $id));
        }

        $this->enter($id);

        try {
            if (!isset($this->definitions[$id])) {
                return $this->resolve($id); // auto-wiring
            }

            $definition = $this->definitions[$id];

            // A string that names a class is a class to build; any other callable is a factory
            $service = is_string($definition) && class_exists($definition)
                ? $this->resolve($definition)
                : (is_callable($definition) ? $definition($this) : $this->resolve((string) $definition));

            if ($this->sharedServices[$id]) {
                $this->services[$id] = $service;
            }

            return $service;
        } finally {
            array_pop($this->resolving);
        }
    }

    /**
     * Whether `get()` can return something for the id: it is defined, or it is an instantiable class.
     */
    public function has(string $id): bool
    {
        if (isset($this->definitions[$id])) {
            return true;
        }

        return class_exists($id) && (new ReflectionClass($id))->isInstantiable();
    }

    private function enter(string $id): void
    {
        if (in_array($id, $this->resolving, true)) {
            throw new CircularDependencyException(sprintf(
                'Circular dependency detected: %s.',
                implode(' -> ', [...$this->resolving, $id]),
            ));
        }

        $this->resolving[] = $id;
    }

    /**
     * Resolves a class by auto-wiring its dependencies.
     *
     * @param string $id The class name to resolve
     * @return mixed The resolved instance
     * @throws NotFoundException|ServiceManagerException
     */
    public function resolve(string $id): mixed
    {
        if (!class_exists($id)) {
            throw new NotFoundException(sprintf('Class "%s" does not exist.', $id));
        }

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
