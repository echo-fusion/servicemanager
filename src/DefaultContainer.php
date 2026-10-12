<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager;

use EchoFusion\ServiceManager\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;
use function sprintf;

class DefaultContainer implements ContainerInterface
{
    protected array $instances = [];

    public function get($id)
    {
        if (!isset($this->instances[$id])) {
            throw new NotFoundException(sprintf('"%s" was not found in the container.', $id));
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
