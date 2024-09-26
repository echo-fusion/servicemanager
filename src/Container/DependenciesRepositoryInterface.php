<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Container;

interface DependenciesRepositoryInterface
{
    public const Factory = 'factory';
    public const Invokable = 'invokable';
    public const Alias = 'alias';

    /**
     * @param non-empty-string $id
     */
    public function get(string $id);

    /**
     * @param non-empty-string $id
     */
    public function has(string $id): bool;

    /**
     * @param non-empty-string $id
     */
    public function getType(string $id): string;

    public function getDependencies(): array;

    public function getFactories(): array;

    public function getInvokables(): array;

    public function getAliases(): array;

    /**
     * @param non-empty-string $id
     * @param non-empty-string|callable $concrete
     */
    public function setFactory(string $id, callable|string $concrete): void;

    /**
     * @param non-empty-string $id
     */
    public function setInvokable(string $id): void;

    /**
     * @param non-empty-string $id
     * @param non-empty-string $concrete
     */
    public function setAlias(string $id, string $concrete): void;
}
