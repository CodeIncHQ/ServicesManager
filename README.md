# PHP service manager

The library is intended to be used as a services manager. It is written in PHP 7.1.

## Usage

```php
<?php
use CodeInc\ServiceManager\ServiceManager;
use CodeInc\ServiceManager\ServiceInterface;

// a first service
class MyFirstService implements ServiceInterface {
	public function hello(string $name):void
	{
		echo sprintf("Hello %s!", $name);
	}
}

// a second service using the first service
class MySecondService implements ServiceInterface {
	/** @var MyFirstService */
	private $myFirstService;
	
	public function __construct(MyFirstService $myFirstService) 
	{
		$this->myFirstService = $myFirstService;
	}
	
	public function helloWorld():void
	{
		$this->myFirstService->hello("World");
	}
}

// calling the second service, the service manager is going to first instantiated MyFirstService
// then instantiate MySecondService with MyFirstService as a parameter.
$serviceManager = new ServiceManager();
$mySecondService = $serviceManager->getInstance(MySecondService::class);
$mySecondService->helloWorld();

// you also can add external objects to makes them available to the servides,
// for instance a PSR-7 ServerRequest object or Doctrine's EntityManager.
$serviceManager->addService($entityManager);

// the service manager will pass the instance of the service manager added
// using addService()
class MyThirdService implements ServiceInterface {
    public function __construct(EntityManager $entityManager) { }
}

``` 


## Installation
This library is available through [Packagist](https://packagist.org/packages/codeinc/lib-servicemanager) and can be installed using [Composer](https://getcomposer.org/): 

```bash
composer require codeinc/lib-servicemanager
```

## License
This library is published under the MIT license (see the [LICENSE](LICENSE) file). 

