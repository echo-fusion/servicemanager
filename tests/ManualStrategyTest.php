<?php

declare(strict_types=1);

use EchoFusion\ServiceManager\Container\DependenciesRepositoryInterface;
use EchoFusion\ServiceManager\Contract\ServiceManagerInterface;
use EchoFusion\ServiceManager\Strategies\ContainerResolverStrategyInterface;
use EchoFusion\ServiceManager\Strategies\ManualStrategy;
use PHPUnit\Framework\TestCase;

class ManualStrategyTest extends TestCase
{
    private ContainerResolverStrategyInterface $manualStrategy;

    protected function setUp(): void
    {
        $this->manualStrategy = new ManualStrategy();
    }

    public function testResolveReturnsInvocableInstance()
    {
        $testClassWithInvocable = new class() {
        };

        $serviceManagerMock = $this->createMock(ServiceManagerInterface::class);
        $dependenciesRepositoryMock = $this->createMock(DependenciesRepositoryInterface::class);
        $serviceManagerMock->method('getDependenciesManager')
            ->willReturn($dependenciesRepositoryMock);

        $dependenciesRepositoryMock->method('getType')
            ->with('serviceInvocable')
            ->willReturn(DependenciesRepositoryInterface::Invokable);

        $dependenciesRepositoryMock->method('get')
            ->with('serviceInvocable')
            ->willReturn(new $testClassWithInvocable());

        $result = $this->manualStrategy->resolve('serviceInvocable', $serviceManagerMock);

        $this->assertInstanceOf($testClassWithInvocable::class, $result);
    }

    public function testResolveReturnsAliasInstance()
    {
        $testClassWithInvocable = new class() {
        };

        $serviceManagerMock = $this->createMock(ServiceManagerInterface::class);
        $dependenciesRepositoryMock = $this->createMock(DependenciesRepositoryInterface::class);
        $serviceManagerMock->method('getDependenciesManager')->willReturn($dependenciesRepositoryMock);

        $aliasId = 'serviceAlias';
        $invocableClass = $testClassWithInvocable::class;

        $dependenciesRepositoryMock->method('getType')
            ->willReturnCallback(function ($id) use ($aliasId, $invocableClass) {
                return match ($id) {
                    $aliasId => DependenciesRepositoryInterface::Alias,
                    $invocableClass => DependenciesRepositoryInterface::Invokable,
                    default => throw new InvalidArgumentException('Unexpected ID provided'),
                };
            });

        $dependenciesRepositoryMock->method('get')
            ->willReturnCallback(function ($id) use ($aliasId, $invocableClass) {
                return match ($id) {
                    $aliasId => $invocableClass,
                    $invocableClass => new $invocableClass(),
                    default => throw new InvalidArgumentException('Unexpected ID provided'),
                };
            });

        $manualStrategy = new ManualStrategy();
        $result = $manualStrategy->resolve($aliasId, $serviceManagerMock);

        $this->assertInstanceOf($testClassWithInvocable::class, $result);
    }

    public function testResolveReturnsFactoryInstance()
    {
        $testClassWithFactory = new class() {
            public function __invoke(ServiceManagerInterface $serviceManager)
            {
                return $this;
            }
        };

        $serviceManagerMock = $this->createMock(ServiceManagerInterface::class);
        $dependenciesRepositoryMock = $this->createMock(DependenciesRepositoryInterface::class);
        $serviceManagerMock->method('getDependenciesManager')
            ->willReturn($dependenciesRepositoryMock);

        $dependenciesRepositoryMock->method('getType')
            ->with('serviceFactory')
            ->willReturn(DependenciesRepositoryInterface::Factory);

        $dependenciesRepositoryMock->method('get')
            ->with('serviceFactory')
            ->willReturn($testClassWithFactory::class);

        $result = $this->manualStrategy->resolve('serviceFactory', $serviceManagerMock);

        $this->assertInstanceOf($testClassWithFactory::class, $result);
    }
}
