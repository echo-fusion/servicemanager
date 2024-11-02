<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class DefaultContainer implements ContainerInterface
{
    protected array $instances = [];

    public function get($id)
    {
        if (!isset($this->instances[$id])) {
            throw new class($id . ' not found') extends Exception implements NotFoundExceptionInterface {
            };
        }

        return $this->instances[$id];
    }

    public function has($id): bool
    {
        return isset($this->instances[$id]);
    }

    public function set(string $id, $instance): void
    {
        $this->instances[$id] = $instance;
    }
}
