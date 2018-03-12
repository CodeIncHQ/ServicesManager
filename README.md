# PHP objects instantiator

The library is intended to be used as an object instantiator. It is written in PHP 7.1.

## Usage

```php
<?php
use CodeInc\Instantiator\Instantiator;

// a first service
class MyFirstClass {
	public function hello(string $name):void
	{
		echo sprintf("Hello %s!", $name);
	}
}

// a second service using the first service
class MySecondClass {
	/** @var MyFirstClass */
	private $myFirstClass;
	
	public function __construct(MyFirstClass $myFirstClass) 
	{
		$this->myFirstClass = $myFirstClass;
	}
	
	public function helloWorld():void
	{
		$this->myFirstClass->hello("World");
	}
}

// calling the second service, the service manager is going to first instantiated MyFirstService
// then instantiate MySecondService with MyFirstService as a parameter.
$serviceManager = new Instantiator();
$mySecondService = $serviceManager->getInstance(MySecondClass::class);
$mySecondService->helloWorld();

// you also can add external objects to makes them available to the servides,
// for instance a PSR-7 ServerRequest object or Doctrine's EntityManager.
$serviceManager->addInstance($entityManager);

// the service manager will pass the instance of the service manager added
// using addService()
class MyThirdService {
    public function __construct(EntityManager $entityManager) { }
}

``` 


## Installation
This library is available through [Packagist](https://packagist.org/packages/codeinc/lib-instantiator) and can be installed using [Composer](https://getcomposer.org/): 

```bash
composer require codeinc/lib-instantiator
```

## License
This library is published under the MIT license (see the [LICENSE](LICENSE) file). 

