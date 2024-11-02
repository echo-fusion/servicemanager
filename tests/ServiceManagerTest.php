<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Tests;

use EchoFusion\ServiceManager\Exceptions\ServiceManagerException;
use EchoFusion\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;

class ServiceManagerTest extends TestCase
{
    private ServiceManager $serviceManager;

    protected function setUp(): void
    {
        $this->serviceManager = new ServiceManager();
    }

    public function testSetAndGetService(): void
    {
        $this->serviceManager->set('testService', fn () => 'test', true);
        $service = $this->serviceManager->get('testService');

        $this->assertEquals('test', $service);
    }

    public function testGetSharedServiceInstance(): void
    {
        $this->serviceManager->set('sharedService', fn () => new stdClass(), true);
        $instance1 = $this->serviceManager->get('sharedService');
        $instance2 = $this->serviceManager->get('sharedService');

        $this->assertSame($instance1, $instance2);
    }

    public function testGetNonSharedServiceInstance(): void
    {
        $this->serviceManager->set('nonSharedService', fn () => new stdClass(), false);
        $instance1 = $this->serviceManager->get('nonSharedService');
        $instance2 = $this->serviceManager->get('nonSharedService');

        $this->assertNotSame($instance1, $instance2);
    }

    public function testServiceOverrideDisabledThrowsException(): void
    {
        $this->serviceManager->set('overrideService', fn () => 'test');

        $this->expectException(ServiceManagerException::class);
        $this->expectExceptionMessage('Service "overrideService" already exists in the container!');

        $this->serviceManager->set('overrideService', fn () => 'testOverride');
    }

    public function testEnableOverrideAllowsOverride(): void
    {
        $this->serviceManager->set('overrideService', fn () => 'test');
        $this->serviceManager->enableOverride(true);

        $this->serviceManager->set('overrideService', fn () => 'testOverride');
        $service = $this->serviceManager->get('overrideService');

        $this->assertEquals('testOverride', $service);
    }

    public function testGetAllowOverride(): void
    {
        $this->assertFalse($this->serviceManager->getAllowOverride());

        $this->serviceManager->enableOverride(true);

        $this->assertTrue($this->serviceManager->getAllowOverride());
    }

    public function testGetUndefinedServiceThrowsException(): void
    {
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('Class "NonExistentService" does not exist');

        $this->serviceManager->get('NonExistentService');
    }

    public function testResolveInstantiableClass(): void
    {
        $this->serviceManager->set(TestClass::class, TestClass::class);
        $service = $this->serviceManager->get(TestClass::class);

        $this->assertInstanceOf(TestClass::class, $service);
    }

    public function testResolveClassWithDependencies(): void
    {
        $this->serviceManager->set(DependencyClass::class, DependencyClass::class);
        $this->serviceManager->set(MainClass::class, MainClass::class);

        $service = $this->serviceManager->get(MainClass::class);

        $this->assertInstanceOf(MainClass::class, $service);
        $this->assertInstanceOf(DependencyClass::class, $service->dependency);
    }

    public function testResolveFailsForMissingTypeHint(): void
    {
        $this->expectException(ServiceManagerException::class);
        $this->expectExceptionMessage('Failed to resolve the class ' . MissingTypeHintClass::class . ' because param param1 is missing a type hint');

        $this->serviceManager->set(MissingTypeHintClass::class, MissingTypeHintClass::class);
        $this->serviceManager->get(MissingTypeHintClass::class);
    }

    public function testResolveFailsForUnionType(): void
    {
        $this->expectException(ServiceManagerException::class);
        $this->expectExceptionMessage('Failed to resolve the class ' . UnionTypeClass::class . ' because of union type of param param1');

        $this->serviceManager->set(UnionTypeClass::class, UnionTypeClass::class);
        $this->serviceManager->get(UnionTypeClass::class);
    }

    public function testResolveFailsForInvalidParam(): void
    {
        $this->expectException(ServiceManagerException::class);
        $this->expectExceptionMessage('Failed to resolve the class ' . InvalidParamClass::class . ' because of invalid param param1');

        $this->serviceManager->set(InvalidParamClass::class, InvalidParamClass::class);
        $this->serviceManager->get(InvalidParamClass::class);
    }

    public function testGetThrowsExceptionForNonInstantiableClass(): void
    {
        $this->expectException(ServiceManagerException::class);
        $this->expectExceptionMessage('Class ' . MyAbstractClass::class . ' is not instantiable!');

        $serviceManager = new ServiceManager();
        $serviceManager->set(MyAbstractClass::class, MyAbstractClass::class);
        $serviceManager->get(MyAbstractClass::class);
    }

    public function testGetInstantiatesClassWithNoConstructorParameters(): void
    {
        $serviceManager = new ServiceManager();

        $serviceManager->set('NoConstructorClass', NoConstructorClass::class);

        $instance = $serviceManager->get('NoConstructorClass');
        $this->assertInstanceOf(NoConstructorClass::class, $instance);
    }

    public function testGetInstantiatesClassWithNoConstructorParametersDirectly(): void
    {
        $serviceManager = new ServiceManager();

        $serviceManager->set('EmptyConstructorClass', EmptyConstructorClass::class);

        $instance = $serviceManager->get('EmptyConstructorClass');
        $this->assertInstanceOf(EmptyConstructorClass::class, $instance);
    }
}

// Mock classes for testing dependencies
class TestClass
{
}

class NoConstructorClass
{
    // No constructor parameters
}

class EmptyConstructorClass
{
    public function __construct()
    {
        // No parameters in constructor
    }
}

abstract class MyAbstractClass
{
    // Abstract classes cannot be instantiated
}

class DependencyClass
{
}

class MainClass
{
    public DependencyClass $dependency;

    public function __construct(DependencyClass $dependency)
    {
        $this->dependency = $dependency;
    }
}

class MissingTypeHintClass
{
    public function __construct($param1)
    {
    }
}

class UnionTypeClass
{
    public function __construct(string|int $param1)
    {
    }
}

class InvalidParamClass
{
    public function __construct(int $param1)
    {
    }
}
