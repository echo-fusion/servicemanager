<?php

declare(strict_types=1);

use EchoFusion\ServiceManager\DependenciesRepositoryInterface;
use EchoFusion\ServiceManager\ServiceManagerException;
use EchoFusion\ServiceManager\ServiceManagerInterface;
use EchoFusion\ServiceManager\Strategies\AutoWiringStrategy;
use EchoFusion\ServiceManager\Strategies\ContainerResolverStrategyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AutoWiringStrategyTest extends TestCase
{
    private ContainerResolverStrategyInterface $autoWiringStrategy;

    private MockObject $serviceManagerMock;

    private MockObject $dependenciesRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->autoWiringStrategy = new AutoWiringStrategy();
        $this->serviceManagerMock = $this->createMock(ServiceManagerInterface::class);
        $this->dependenciesRepositoryMock = $this->createMock(DependenciesRepositoryInterface::class);

        $this->serviceManagerMock->method('getDependenciesManager')
            ->willReturn($this->dependenciesRepositoryMock);
    }

    public function testResolveInstantiableClassWithoutConstructor(): void
    {
        $testClassWithoutConstructor = new class() {
        };

        $this->dependenciesRepositoryMock->method('get')
            ->with($testClassWithoutConstructor::class)
            ->willReturn($testClassWithoutConstructor);

        $result = $this->autoWiringStrategy->resolve($testClassWithoutConstructor::class, $this->serviceManagerMock);
        $this->assertInstanceOf($testClassWithoutConstructor::class, $result);
    }

    public function testResolveClassWithConstructor(): void
    {
        $testClassWithConstructor = new class() {
            public function __construct()
            {
            }
        };

        $this->dependenciesRepositoryMock->method('get')
            ->with($testClassWithConstructor::class)
            ->willReturn($testClassWithConstructor);

        $this->dependenciesRepositoryMock->method('get')
            ->willReturn($testClassWithConstructor::class);

        $result = $this->autoWiringStrategy->resolve($testClassWithConstructor::class, $this->serviceManagerMock);
        $this->assertInstanceOf($testClassWithConstructor::class, $result);
    }

    public function testResolveThrowsExceptionForNonInstantiableClass()
    {
        $this->expectException(ServiceManagerException::class);
        $this->expectExceptionMessage('Class ' . NonInstantiableClass::class . ' is not instantiable!');

        $serviceManagerMock = $this->createMock(ServiceManagerInterface::class);
        $dependenciesRepositoryMock = $this->createMock(DependenciesRepositoryInterface::class);
        $serviceManagerMock->method('getDependenciesManager')->willReturn($dependenciesRepositoryMock);

        $dependenciesRepositoryMock
            ->method('get')
            ->willReturn(NonInstantiableClass::class);

        $autoWiringStrategy = new AutoWiringStrategy();
        $autoWiringStrategy->resolve('NonInstantiableClass', $serviceManagerMock);
    }

    public function testResolveThrowsExceptionForMissingTypeHint()
    {
        $testClassWithMissingTypeHint = new class(101) {
            public function __construct($param1)
            {
            }
        };

        $this->expectException(ServiceManagerException::class);
        $this->expectExceptionMessage('Failed to resolve the class ' . $testClassWithMissingTypeHint::class . ' because param param1 is missing a type hint');

        $serviceManagerMock = $this->createMock(ServiceManagerInterface::class);
        $dependenciesRepositoryMock = $this->createMock(DependenciesRepositoryInterface::class);
        $serviceManagerMock->method('getDependenciesManager')->willReturn($dependenciesRepositoryMock);

        $dependenciesRepositoryMock
            ->method('get')
            ->willReturn($testClassWithMissingTypeHint::class);

        $autoWiringStrategy = new AutoWiringStrategy();
        $autoWiringStrategy->resolve('TestClassWithMissingTypeHint', $serviceManagerMock);
    }

    public function testResolveThrowsExceptionForUnionType()
    {
        $testClassWithUnionType = new class(101) {
            public function __construct(string|int $param1)
            {
            }
        };

        $this->expectException(ServiceManagerException::class);
        $this->expectExceptionMessage('Failed to resolve the class ' . $testClassWithUnionType::class . ' because of union type of param param1');

        $serviceManagerMock = $this->createMock(ServiceManagerInterface::class);
        $dependenciesRepositoryMock = $this->createMock(DependenciesRepositoryInterface::class);
        $serviceManagerMock->method('getDependenciesManager')->willReturn($dependenciesRepositoryMock);

        $dependenciesRepositoryMock
            ->method('get')
            ->willReturn($testClassWithUnionType::class);

        $autoWiringStrategy = new AutoWiringStrategy();
        $autoWiringStrategy->resolve('TestClassWithUnionType', $serviceManagerMock);
    }

    public function testResolveThrowsExceptionForInvalidParams(): void
    {
        $testClassWithInvalidParams = new class('example') {
            public function __construct(string $param1)
            {
            }
        };

        $this->expectException(ServiceManagerException::class);
        $this->expectExceptionMessage('Failed to resolve the class ' . $testClassWithInvalidParams::class . ' because invalid params!');

        $this->dependenciesRepositoryMock
            ->method('get')
            ->willReturn($testClassWithInvalidParams::class);

        $this->autoWiringStrategy->resolve($testClassWithInvalidParams::class, $this->serviceManagerMock);
    }

    public function testResolveInvokableClass(): void
    {
        $testClassInvokable = new class() {
            public function __invoke(ServiceManagerInterface $serviceManager)
            {
                return new self();
            }
        };

        $this->dependenciesRepositoryMock
            ->method('get')
            ->willReturn($testClassInvokable::class);

        $result = $this->autoWiringStrategy->resolve($testClassInvokable::class, $this->serviceManagerMock);

        $this->assertInstanceOf($testClassInvokable::class, $result);
    }
}

abstract class NonInstantiableClass
{
}
