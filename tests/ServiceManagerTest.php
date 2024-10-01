<?php

declare(strict_types=1);

use EchoFusion\ServiceManager\DependenciesRepositoryInterface;
use EchoFusion\ServiceManager\ServiceManager;
use EchoFusion\ServiceManager\ServiceManagerException;
use EchoFusion\ServiceManager\ServiceManagerInterface;
use EchoFusion\ServiceManager\Strategies\ContainerResolverStrategyInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

class ServiceManagerTest extends TestCase
{
    private ServiceManagerInterface $serviceManager;

    private DependenciesRepositoryInterface $dependenciesRepository;

    private ContainerResolverStrategyInterface $containerResolverStrategy;

    protected function setUp(): void
    {
        $this->dependenciesRepository = $this->createMock(DependenciesRepositoryInterface::class);
        $this->containerResolverStrategy = $this->createMock(ContainerResolverStrategyInterface::class);

        $this->serviceManager = new ServiceManager(
            $this->dependenciesRepository,
            $this->containerResolverStrategy,
            false
        );
    }

    public function testGetReturnsCallableService(): void
    {
        $this->dependenciesRepository->method('has')
            ->with('serviceA')
            ->willReturn(true);

        $this->dependenciesRepository->method('get')
            ->with('serviceA')
            ->willReturn(fn () => 'resolvedService');

        $result = $this->serviceManager->get('serviceA');

        $this->assertEquals('resolvedService', $result);
    }

    public function testGetResolvesServiceWhenNotFound(): void
    {
        $resolvedObj = new stdClass();

        $this->dependenciesRepository->method('has')
            ->with('serviceA')
            ->willReturn(false);

        $this->containerResolverStrategy->method('resolve')
            ->with('serviceA', $this->serviceManager)
            ->willReturn($resolvedObj);

        $result = $this->serviceManager->get('serviceA');

        $this->assertEquals($resolvedObj, $result);
    }

    public function testGetReturnsNonCallableService(): void
    {
        $resolvedObj = new stdClass();

        $this->dependenciesRepository->method('has')
            ->with('serviceA')
            ->willReturn(true);

        $this->dependenciesRepository->method('get')
            ->with('serviceA')
            ->willReturn('nonCallableService');

        $this->containerResolverStrategy->method('resolve')
            ->with('serviceA', $this->serviceManager)
            ->willReturn($resolvedObj);

        $result = $this->serviceManager->get('serviceA');

        $this->assertEquals($resolvedObj, $result);
    }

    public function testHasServiceExists(): void
    {
        $this->dependenciesRepository->method('has')
            ->with('serviceA')
            ->willReturn(true);

        $this->assertTrue($this->serviceManager->has('serviceA'));
    }

    public function testBindThrowsExceptionIfServiceExistsAndOverrideNotAllowed(): void
    {
        $this->dependenciesRepository->method('getDependencies')
            ->willReturn(['serviceA' => 'FactoryClassA']);

        $this->dependenciesRepository->method('has')
            ->with('serviceA')
            ->willReturn(true);

        $this->expectException(ServiceManagerException::class);
        $this->expectExceptionMessage('Dependency "serviceA" is already exist in container!');

        $this->serviceManager->bind($this->dependenciesRepository);
    }

    public function testBindAddsServices(): void
    {
        $this->dependenciesRepository->method('getDependencies')
            ->willReturn([
                'serviceA' => 'FactoryClassA',
                'serviceB' => 'InvokableClassB',
                'serviceC' => 'AliasClassC',
            ]);

        $this->dependenciesRepository->method('has')
            ->willReturn(false);

        $this->dependenciesRepository->method('getType')
            ->willReturnOnConsecutiveCalls(
                DependenciesRepositoryInterface::Factory,
                DependenciesRepositoryInterface::Invokable,
                DependenciesRepositoryInterface::Alias
            );

        $this->dependenciesRepository->expects($this->once())
            ->method('setFactory')
            ->with('serviceA', 'FactoryClassA');

        $this->dependenciesRepository->expects($this->once())
            ->method('setInvokable')
            ->with('InvokableClassB');

        $this->dependenciesRepository->expects($this->once())
            ->method('setAlias')
            ->with('serviceC', 'AliasClassC');

        $this->serviceManager->bind($this->dependenciesRepository);
    }

    public function testBindAddsServicesWithOverrideAllowed(): void
    {
        $this->serviceManager = new ServiceManager(
            $this->dependenciesRepository,
            $this->containerResolverStrategy,
            true
        );

        $this->dependenciesRepository->method('getDependencies')
            ->willReturn(['serviceA' => 'FactoryClassA']);

        $this->dependenciesRepository->method('has')
            ->with('serviceA')
            ->willReturn(true);

        $this->dependenciesRepository->method('getType')
            ->willReturn(DependenciesRepositoryInterface::Factory);

        $this->dependenciesRepository->expects($this->once())
            ->method('setFactory')
            ->with('serviceA', 'FactoryClassA');

        $this->serviceManager->bind($this->dependenciesRepository);
    }

    public function testBindDoesNotThrowExceptionIfNoDependencies(): void
    {
        $this->dependenciesRepository->method('getDependencies')
            ->willReturn([]);

        try {
            $this->serviceManager->bind($this->dependenciesRepository);
        } catch (ServiceManagerException $e) {
            $this->fail('Expected no exception to be thrown, but got: ' . $e->getMessage());
        }

        $this->assertTrue(true);
    }

    public function testResolveService(): void
    {
        $resolvedObj = new stdClass();

        $this->containerResolverStrategy->method('resolve')
            ->with('serviceA', $this->serviceManager)
            ->willReturn($resolvedObj);

        $result = $this->serviceManager->resolve('serviceA');

        $this->assertEquals($resolvedObj, $result);
    }

    public function testGetDependenciesManager(): void
    {
        $result = $this->serviceManager->getDependenciesManager();

        $this->assertSame($this->dependenciesRepository, $result);
    }
}
