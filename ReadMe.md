# Service manager

The ServiceManager package is a lightweight, flexible dependency injection container for managing services in PHP applications. It supports features like singleton services, auto-wiring, and lazy loading, making it easy to define and resolve services of varying complexity.

With ServiceManagerProvider, the package can be seamlessly integrated into applications that use PSR-11-compatible containers.

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
use EchoFusion\ServiceManager\ServiceManagerInterface;

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

