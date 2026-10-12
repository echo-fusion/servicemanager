<?php

declare(strict_types=1);

namespace EchoFusion\ServiceManager\Tests;

use EchoFusion\ServiceManager\Exceptions\CircularDependencyException;
use EchoFusion\ServiceManager\Exceptions\NotFoundException;
use EchoFusion\ServiceManager\Exceptions\ServiceManagerException;
use EchoFusion\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
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
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Service "NonExistentService" is not defined and is not an instantiable class.');

        $this->serviceManager->get('NonExistentService');
    }

    public function testSharedFlagIsHonouredForClassNameDefinitions(): void
    {
        $this->serviceManager->set('shared.class', stdClass::class, true);
        $this->serviceManager->set('plain.class', stdClass::class);

        $this->assertSame($this->serviceManager->get('shared.class'), $this->serviceManager->get('shared.class'));
        $this->assertNotSame($this->serviceManager->get('plain.class'), $this->serviceManager->get('plain.class'));
    }

    public function testOverridingASharedServiceDropsTheOldInstance(): void
    {
        $this->serviceManager->enableOverride(true);
        $this->serviceManager->set('service', fn () => new stdClass(), true);
        $old = $this->serviceManager->get('service');

        $this->serviceManager->set('service', fn () => new DependencyClass(), true);

        $this->assertInstanceOf(DependencyClass::class, $this->serviceManager->get('service'));
        $this->assertNotSame($old, $this->serviceManager->get('service'));
    }

    public function testASharedServiceThatIsNullIsBuiltOnlyOnce(): void
    {
        $calls = 0;
        $this->serviceManager->set('nothing', function () use (&$calls) {
            ++$calls;

            return null;
        }, true);

        $this->assertNull($this->serviceManager->get('nothing'));
        $this->assertNull($this->serviceManager->get('nothing'));
        $this->assertSame(1, $calls);
    }

    public function testHasFollowsPsr11ForAutoWirableClasses(): void
    {
        $this->serviceManager->set('defined', fn () => 1);

        $this->assertTrue($this->serviceManager->has('defined'));
        $this->assertTrue($this->serviceManager->has(DependencyClass::class), 'an instantiable class can be auto-wired');
        $this->assertFalse($this->serviceManager->has(MyAbstractClass::class), 'an abstract class cannot');
        $this->assertFalse($this->serviceManager->has('not-a-class'));
    }

    public function testUnknownIdsThrowAPsr11NotFoundException(): void
    {
        try {
            $this->serviceManager->get('nope');
            $this->fail('Expected NotFoundException');
        } catch (NotFoundException $e) {
            $this->assertInstanceOf(NotFoundExceptionInterface::class, $e);
        }
    }

    public function testCircularClassDependenciesAreReportedInsteadOfCrashing(): void
    {
        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage('Circular dependency detected: ' . CircularA::class . ' -> ' . CircularB::class . ' -> ' . CircularA::class . '.');

        $this->serviceManager->get(CircularA::class);
    }

    public function testCircularFactoryDependenciesAreReported(): void
    {
        $this->serviceManager->set('a', fn ($c) => $c->get('b'));
        $this->serviceManager->set('b', fn ($c) => $c->get('a'));

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage('a -> b -> a');

        $this->serviceManager->get('a');
    }

    public function testTheContainerRecoversAfterAFailedResolution(): void
    {
        try {
            $this->serviceManager->get(CircularA::class);
        } catch (CircularDependencyException) {
        }

        $this->assertInstanceOf(DependencyClass::class, $this->serviceManager->get(DependencyClass::class), 'the resolving stack was cleaned up');
    }

    public function testAClassNameThatIsAlsoAFunctionNameIsTreatedAsAClass(): void
    {
        $this->serviceManager->set('dt', stdClass::class);

        $this->assertInstanceOf(stdClass::class, $this->serviceManager->get('dt'));
    }

    public function testResolveReportsMissingClasses(): void
    {
        $this->expectException(NotFoundException::class);

        $this->serviceManager->resolve('Does\\Not\\Exist');
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

class CircularA
{
    public function __construct(public CircularB $b)
    {
    }
}

class CircularB
{
    public function __construct(public CircularA $a)
    {
    }
}
