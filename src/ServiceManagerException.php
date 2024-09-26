<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager;

use Psr\Container\ContainerExceptionInterface;

class ServiceManagerException extends \Exception implements ContainerExceptionInterface
{
}