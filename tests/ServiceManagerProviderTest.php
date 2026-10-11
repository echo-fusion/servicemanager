<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Tests;

use EchoFusion\Contracts\ServiceManager\ServiceManagerInterface;
use EchoFusion\Contracts\ServiceManager\SettableContainerInterface;
use EchoFusion\ServiceManager\Exceptions\ServiceManagerException;
use EchoFusion\ServiceManager\Providers\ServiceManagerProvider;
use PHPUnit\Framework\TestCase;
use function is_callable;

class ServiceManagerProviderTest extends TestCase
{
    private ServiceManagerProvider $provider;

    private SettableContainerInterface $containerMock;

    protected function setUp(): void
    {
        $this->provider = new ServiceManagerProvider();
        $this->containerMock = $this->createMock(SettableContainerInterface::class);
    }

    public function testRegisterSetsServiceManager(): void
    {
        $this->containerMock
            ->expects($this->once())
            ->method('set')
            ->with(ServiceManagerInterface::class, $this->isType('callable'));

        $this->provider->register($this->containerMock);
    }

    public function testBootWithAllowOverride(): void
    {
        $serviceManagerMock = $this->createMock(ServiceManagerInterface::class);

        $this->containerMock
            ->method('get')
            ->with(ServiceManagerInterface::class)
            ->willReturn($serviceManagerMock);

        $serviceManagerMock
            ->expects($this->once())
            ->method('enableOverride')
            ->with(true);

        $this->provider->boot($this->containerMock, ['allow_override' => true]);
    }

    public function testBootWithDependencies(): void
    {
        $serviceManagerMock = $this->createMock(SettableContainerInterface::class);

        // Set up the container to return the mock service manager when requested
        $this->containerMock
            ->method('get')
            ->with(ServiceManagerInterface::class)
            ->willReturn($serviceManagerMock);

        // Expecting 3 calls to `set`:
        // - The first two are for the dependencies
        // - The third one is for persisting ServiceManagerInterface with itself
        $serviceManagerMock
            ->expects($this->exactly(3))
            ->method('set')
            ->withConsecutive(
                ['MyServiceInterface', 'MyService'],
                ['AnotherServiceInterface', $this->isType('callable')],
                [ServiceManagerInterface::class, $this->isType('callable')]
            );

        // Run the `boot` method with the dependencies configuration
        $this->provider->boot($this->containerMock, [
            'dependencies' => [
                'MyServiceInterface' => 'MyService',
                'AnotherServiceInterface' => fn () => 'AnotherService',
            ],
        ]);
    }

    public function testBootWithoutSettableContainer(): void
    {
        $serviceManagerMock = $this->createMock(ServiceManagerInterface::class);

        $this->containerMock
            ->method('get')
            ->with(ServiceManagerInterface::class)
            ->willReturn($serviceManagerMock);

        // Simulate a non-SettableContainer
        $this->containerMock
            ->method('set')
            ->willThrowException(new ServiceManagerException('Container does not implement SettableContainerInterface'));

        $this->provider->boot($this->containerMock, []);

        // No exception should be thrown when container is not settable.
        $this->assertTrue(true);
    }

    public function testRegisterSetsServiceManagerInContainer(): void
    {
        $containerMock = $this->createMock(SettableContainerInterface::class);

        // Expect `set` to be called with ServiceManagerInterface and a callable that returns ServiceManager
        $containerMock->expects($this->once())
            ->method('set')
            ->with(
                ServiceManagerInterface::class,
                $this->callback(function ($callable) {
                    return is_callable($callable) && $callable() instanceof ServiceManagerInterface;
                })
            );

        $provider = new ServiceManagerProvider();
        $provider->register($containerMock);
    }

    public function testBootConfiguresServiceManagerWithAllowOverrideAndDependencies(): void
    {
        // Mock SettableContainerInterface instead of ServiceManagerInterface
        $serviceManagerMock = $this->createMock(ServiceManagerInterface::class);

        // Expect enableOverride to be called once on the service manager
        $serviceManagerMock->expects($this->once())
            ->method('enableOverride')
            ->with(true);

        // Expect set to be called twice on the service manager with the specific arguments
        $serviceManagerMock->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                ['MyServiceInterface', 'MyService'],
                [ServiceManagerInterface::class, $this->isType('callable')]
            );

        // Mock the container and ensure it returns our service manager mock
        $settableContainerMock = $this->createMock(SettableContainerInterface::class);

        $settableContainerMock->expects($this->once())
            ->method('get')
            ->with(ServiceManagerInterface::class)
            ->willReturn($serviceManagerMock);

        $provider = new ServiceManagerProvider();

        // Call the boot method with config for override and dependencies
        $provider->boot($settableContainerMock, [
            'allow_override' => true,
            'dependencies' => [
                'MyServiceInterface' => 'MyService',
            ],
        ]);
    }
}
