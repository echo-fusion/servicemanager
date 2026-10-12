# ServiceManager

[![CI](https://github.com/echo-fusion/servicemanager/actions/workflows/ci.yml/badge.svg)](https://github.com/echo-fusion/servicemanager/actions/workflows/ci.yml) [![Packagist](https://img.shields.io/packagist/v/echo-fusion/servicemanager.svg)](https://packagist.org/packages/echo-fusion/servicemanager) [![PHP](https://img.shields.io/badge/php-8.1%20%7C%208.2%20%7C%208.3-777bb4.svg)](https://www.php.net) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

The ServiceManager package is a lightweight, flexible dependency injection container for managing services in PHP applications. It supports features like singleton services, auto-wiring, and lazy loading, making it easy to define and resolve services of varying complexity.

With ServiceManagerProvider, the package can be seamlessly integrated into applications that use PSR-11-compatible containers.

> Part of [EchoFusion](https://github.com/echo-fusion), a modular PHP framework built from small packages.

## Installation

```bash
composer require echo-fusion/servicemanager
```

## Requirements

* PHP 8.1, 8.2 or 8.3

## Key Features

* Auto-wiring: Automatically resolves dependencies for class constructors.
* Singleton support: Define services as shared (singleton) instances.
* Lazy loading: Services are instantiated only when requested.
* Configurable overrides: Allow or restrict overriding existing services.

## Usage

### Basic Setup
* Register Services: Add services to the container with set().
* Resolve Services: Retrieve instances via get().
* Enable Override: Allow service overrides using enableOverride().

This example demonstrates how to register and retrieve services, configure singletons, and enable auto-wiring for classes:

```php
use EchoFusion\ServiceManager\ServiceManager;
use EchoFusion\ServiceManager\Exceptions\ServiceManagerException;

$serviceManager = new ServiceManager();

// Register a service with an ID and class name (shared as singleton)
$serviceManager->set(MyServiceInterface::class, MyService::class, true);

// Register a service with a factory (non-singleton)
$serviceManager->set(AnotherServiceInterface::class, function (ServiceManagerInterface $sm) {
    return new AnotherService($sm->get(MyServiceInterface::class));
});

// Retrieve services
$myService = $serviceManager->get(MyServiceInterface::class);
$anotherService = $serviceManager->get(AnotherServiceInterface::class);

// Check if a service exists
if ($serviceManager->has(MyServiceInterface::class)) {
    // ...
}
```

### Using provider
The ServiceManagerProvider integrates ServiceManager into a PSR-11-compatible container and manages service booting and configuration.

**Note:** Copy the config file into your project's config directory and add your application's dependencies following the provided example. 

```php
use EchoFusion\ServiceManager\DefaultContainer;
use EchoFusion\ServiceManager\Providers\ServiceManagerProvider;
use EchoFusion\Contracts\ServiceManager\ServiceManagerInterface;

// Initialize a container, falling back to DefaultContainer if $container is not provided
$container = $container ?? new DefaultContainer();

// Initialize the ServiceManagerProvider with the container
$provider = new ServiceManagerProvider();
// Register the ServiceManager within the container
$provider->register($container);
// Define and boot service configurations
$config = require __DIR__ . '/config/servicemanager.config.php';// replace path with your project config directory
$provider->boot($container, $config]);

// Retrieve the service manager from the container
$serviceManager = $container->get(ServiceManagerInterface::class);

// Now you can access services through $serviceManager
if ($serviceManager->has(MyServiceInterface::class)) {
    $myService = $serviceManager->get(MyServiceInterface::class);
}

// or if you want to get exception if service definition doesn't exist
try {
    $serviceManager->get(AnotherServiceInterface::class);
} catch (ServiceManagerException | ReflectionException $e) {
    echo $e->getMessage();
}
```

## Behaviour worth knowing

* **Shared vs new:** a service defined with `shared: true` is built once and reused, whether its definition is a factory or a class name. Without it, every `get()` builds a new instance.
* **Auto-wiring:** an id that has no definition but names an instantiable class is built by resolving its constructor type-hints from the container. Auto-wired instances are **not** shared; define the class with `shared: true` when you want a singleton.
* **PSR-11:** `has()` is `true` for defined services and for instantiable classes, and `get()` of anything else throws `Exceptions\NotFoundException` (a `Psr\Container\NotFoundExceptionInterface`).
* **Loops:** services that depend on each other in a loop throw `Exceptions\CircularDependencyException` naming the chain (`A -> B -> A`) instead of exhausting the stack.
* **Constructor parameters:** a class-typed parameter is taken from the container. A parameter the container cannot provide (a scalar, an untyped or union-typed one, or an interface nobody defined) uses its **default value** when it has one, so `__construct(Foo $foo, int $retries = 3, ?LoggerInterface $logger = null)` works. Without a default it throws, naming the parameter. Variadic parameters stay empty.
* **Overriding:** `set()` on an existing id needs `enableOverride(true)`; the previously built shared instance is dropped.

## Testing

```bash
composer test
```

## Credits

- [Amir Shadanfar](https://github.com/amir-shadanfar)

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
