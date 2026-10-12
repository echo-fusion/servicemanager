<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends ServiceManagerException implements NotFoundExceptionInterface
{
}
