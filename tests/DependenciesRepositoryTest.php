<?php

declare(strict_types=1);

use EchoFusion\ServiceManager\Container\DependenciesRepository;
use EchoFusion\ServiceManager\Container\DependenciesRepositoryInterface;
use PHPUnit\Framework\TestCase;
use EchoFusion\ServiceManager\ServiceManagerException;

class DependenciesRepositoryTest extends TestCase
{
    private DependenciesRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = new DependenciesRepository();
    }

    public function testSetAndGetFactory(): void
    {
        $this->repository->setFactory('serviceA', 'FactoryClassA');
        $factories = $this->repository->getFactories();

        $this->assertArrayHasKey('serviceA', $factories);
        $this->assertEquals('FactoryClassA', $factories['serviceA']);
    }

    public function testSetAndGetInvokable(): void
    {
        $this->repository->setInvokable('serviceB');
        $invokables = $this->repository->getInvokables();

        $this->assertArrayHasKey('serviceB', $invokables);
        $this->assertEquals('serviceB', $invokables['serviceB']);
    }

    public function testSetAndGetAlias(): void
    {
        $this->repository->setAlias('serviceC', 'serviceD');
        $aliases = $this->repository->getAliases();

        $this->assertArrayHasKey('serviceC', $aliases);
        $this->assertEquals('serviceD', $aliases['serviceC']);
    }

    public function testGetTypeForAlias(): void
    {
        $this->repository->setAlias('serviceC', 'serviceD');
        $this->assertEquals(DependenciesRepositoryInterface::Alias, $this->repository->getType('serviceC'));
    }

    public function testGetTypeForInvokable(): void
    {
        $this->repository->setInvokable('serviceB');
        $this->assertEquals(DependenciesRepositoryInterface::Invokable, $this->repository->getType('serviceB'));
    }

    public function testGetTypeForFactory(): void
    {
        $this->repository->setFactory('serviceA', 'FactoryClassA');
        $this->assertEquals(DependenciesRepositoryInterface::Factory, $this->repository->getType('serviceA'));
    }

    public function testGetTypeThrowsExceptionForNonexistentService(): void
    {
        $this->expectException(ServiceManagerException::class);
        $this->repository->getType('unknownService');
    }

    public function testHas(): void
    {
        $this->repository->setFactory('serviceA', 'FactoryClassA');
        $this->assertTrue($this->repository->has('serviceA'));

        $this->assertFalse($this->repository->has('nonExistentService'));
    }

    public function testGetService(): void
    {
        $this->repository->setFactory('serviceA', 'FactoryClassA');
        $this->assertEquals('FactoryClassA', $this->repository->get('serviceA'));
    }

    public function testGetThrowsExceptionForNonexistentService(): void
    {
        $this->expectException(ServiceManagerException::class);
        $this->repository->get('nonExistentService');
    }

    public function testGetDependencies(): void
    {
        $this->repository->setFactory('serviceA', 'FactoryClassA');
        $this->repository->setInvokable('serviceB');
        $this->repository->setAlias('serviceC', 'serviceD');

        $dependencies = $this->repository->getDependencies();

        $this->assertArrayHasKey('serviceA', $dependencies);
        $this->assertArrayHasKey('serviceB', $dependencies);
        $this->assertArrayHasKey('serviceC', $dependencies);
    }
}
