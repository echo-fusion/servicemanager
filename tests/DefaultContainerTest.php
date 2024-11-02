<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Tests;

use EchoFusion\ServiceManager\DefaultContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

class DefaultContainerTest extends TestCase
{
    public function testSetAndGetInstance(): void
    {
        $container = new DefaultContainer();
        $service = new stdClass();

        // Set a service instance
        $container->set('my_service', $service);

        // Retrieve the same service instance
        $retrievedService = $container->get('my_service');
        $this->assertSame($service, $retrievedService);
    }

    public function testHasReturnsTrueForExistingService(): void
    {
        $container = new DefaultContainer();
        $service = new stdClass();

        // Set a service instance
        $container->set('existing_service', $service);

        // Verify the service is present
        $this->assertTrue($container->has('existing_service'));
    }

    public function testHasReturnsFalseForNonExistingService(): void
    {
        $container = new DefaultContainer();

        // Verify the service is not present
        $this->assertFalse($container->has('non_existent_service'));
    }

    public function testGetThrowsExceptionForNonExistingService(): void
    {
        $container = new DefaultContainer();

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('non_existent_service not found');

        // Attempt to retrieve a non-existent service
        $container->get('non_existent_service');
    }
}
