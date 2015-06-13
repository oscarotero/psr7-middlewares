# psr7-middlewares

Collection of PSR-7 middlewares

## Requirements

* A PSR-7 HTTP Message implementation, for example [zend-diactoros](https://github.com/zendframework/zend-diactoros)
* A PSR-7 middleware dispatcher, for example [Relay](https://github.com/relayphp/Relay.Relay)

All middlewares follow this pattern:

* Receive the incoming request and response objects from the previous middleware as parameters, along with the next middleware as a callable.
* Optionally modify the received request and response as desired.
* Optionally invoke the next middleware with the request and response, receiving a new response in return.
* Optionally modify the returned response as desired.
* Return the response to the previous middleware.


## Usage example:

```php
use Relay\Relay;
use Psr7Middlewares;
use Aura\Router\RouterContainer;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

$dispatcher = new Relay([
    new Psr7Middlewares\DigestAuthentication([
    	'user1' => 'pass1',
    	'user2' => 'pass2']
    ),
    new Psr7Middlewares\AuraRouter(function () {
    	$router = new RouterContainer();

    	$router->getMap()->get('home', '/', function ($request, $response) {
    		$response->getBody()->write('hello world');
            return $response;
    	});
    }),
]);

$response = $dispatcher(ServerRequestFactory::fromGlobals(), new Response());
```

## Available middlewares

* **AuraRouter** To execute the [Aura.Router](https://github.com/auraphp/Aura.Router) as a middleware. You must use the 3.x version, compatible with psr-7
* **BasicAuthentication** To provide the basic http authentication.
* **DigestAuthentication** To provide the digest http authentication.
