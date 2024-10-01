# Service manager

This is a lightweight, flexible dependency injection container designed to manage services and their dependencies in PHP applications. It simplifies the process of binding, resolving, and managing service instances using multiple strategies, such as factories, invokables, and aliases.

Built with flexibility in mind, the ServiceManager allows you to define how your dependencies are resolved, making it ideal for small to large-scale applications. It follows modern PHP standards and supports PSR-11 compatible containers, ensuring seamless integration with other frameworks and libraries.

## Install

Via Composer

``` bash
$ composer require echo-fusion/servicemanager
```

## Requirements

The following versions of PHP are supported by this version.

* PHP 8.1
* PHP 8.2
* PHP 8.3

## Usage

This example demonstrates how to bind three types of services:

- Invokable: Classes that can be instantiated directly without dependencies
- Factory: Classes that require their dependencies to be defined separately, either in a factory class or as a callable
- Alias: Interfaces that are mapped to specific class implementations

```php
<?php

require 'vendor/autoload.php';

use EchoFusion\ServiceManager\ServiceManager;
use EchoFusion\ServiceManager\DependenciesRepository;
use EchoFusion\ServiceManager\Strategies\ContainerResolverStrategy;
use EchoFusion\ServiceManager\ServiceManagerInterface;
use EchoFusion\ServiceManager\DependenciesRepositoryInterface;

// Create a Dependencies Repository
$dependenciesRepository = new DependenciesRepository();

// Bind services
function(DependenciesRepositoryInterface $dependenciesRepository) {
    $dependenciesRepository->setAlias(MyServiceInterface::class, MyServiceFactory::class);
    $dependenciesRepository->setInvokable(MyService::class, MyService::class);
    $dependenciesRepository->setFactory(AnotherServiceInterface::class, function (ServiceManagerInterface $serviceManager) {
        return new AnotherService($serviceManager->get('MyServiceInterface'));
    });
    // ...
}
```
When deciding how to define a factory, the choice between a callable and a factory class depends on the complexity and reusability of the creation logic:
- Callable: Best for simple, single-use creation logic
- Factory class: Ideal for complex or reusable service creation logic

```php
use EchoFusion\ServiceManager\ServiceManagerInterface;

class DBFactory
{
    public function __invoke(ServiceManagerInterface $serviceManager): DBInterface
    {
        $config = $serviceManager->get(ConfigInterface::class);
        \Webmozart\Assert\Assert::isInstanceOf($config, ConfigInterface::class);
        \Webmozart\Assert\Assert::keyExists($configArray, 'database_info');
        // do some complex validation... 
        
        return new DB(
            $configArray['database_info']
        );
    }
}

// don't forget to define this factory class inside your dependencies repository
$dependenciesRepository->setFactory(DBInterface::class, DBFactory::class);
```

After defining your dependencies, you need to bind them to the service manager:

```php
use EchoFusion\ServiceManager\ServiceManager;

// Create a Container Resolver Strategy according config
$resolver = new $config['service_manager']['resolver'];

// Create a Service Manager instance
$serviceManager = new ServiceManager(
    $dependenciesRepository,
    $resolver,
    false // allowOverride services inside the container
);

$serviceManager->bind($dependenciesRepository);

// Resolve services
$myService = $serviceManager->get(myServiceInterface::class);
// check service exist on service manager
if ($serviceManager->has(AnotherServiceInterface::class)) {
    $anotherService = $serviceManager->get(AnotherServiceInterface::class);
}
```

If you attempt to bind a service that already exists and overrides are not allowed, a ServiceManagerException will be thrown:

```php
try {
    $serviceManager->bind($dependenciesRepository);
} catch (ServiceManagerException $e) {
    echo $e->getMessage(); // Handle exception
}
```


## Testing

Testing includes PHPUnit and PHPStan (Level 7).

``` bash
$ composer test
```

## Credits

Developed and maintained by [Amir Shadanfar](https://github.com/amir-shadanfar).  
Connect on [LinkedIn](https://www.linkedin.com/in/amir-shadanfar).

## License

The MIT License (MIT). Please see [License File](https://github.com/echo-fusion/servicemanager/blob/main/LICENSE) for more information.

