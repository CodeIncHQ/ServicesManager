# Services manager

The library is intended to be used as a services manager. It is written in PHP 7.1.

## Usage

```php
<?php
use CodeInc\ServicesManager\ServicesManager;

// a first service
class ServiceA  
{
	public function hello(string $name):void
	{
		echo sprintf("Hello %s!", $name);
	}
}

// a second service using the first service
class ServiceB 
 {
	/** @var MyFirstService */
	private $serviceA;
	
	public function __construct(ServiceA $serviceA) 
	{
		$this->serviceA = $serviceA;
	}
	
	public function helloWorld():void
	{
		$this->serviceA->hello("World");
	}
}

// calling the second service, the service manager is going to first instantiated MyFirstService
// then instantiate MySecondService with MyFirstService as a parameter.
$serviceManager = new ServicesManager();
$serviceB = $serviceManager->getService(ServiceB::class);
$serviceB->helloWorld();

// you also can add external objects to makes them available to the servides,
// for instance Doctrine's EntityManager.
$serviceManager->addService($entityManager);

// the service manager will pass the instance of the service manager added
// using addService()
class ServiceC {
    public function __construct(EntityManager $entityManager) { }
}

``` 


## Installation
This library is available through [Packagist](https://packagist.org/packages/codeinc/services-manager) and can be installed using [Composer](https://getcomposer.org/): 

```bash
composer require codeinc/services-manager
```

## License
This library is published under the MIT license (see the [LICENSE](LICENSE) file). 

