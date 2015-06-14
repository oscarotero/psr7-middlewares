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
use Psr7Middlewares\Middleware;

use Relay\Relay;
use Aura\Router\RouterContainer;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

$dispatcher = new Relay([
    Middleware::BasePath('/my-site/web'),
    Middleware::DigestAuthentication(['username' => 'password']),
    Middleware::ClientIp(),
    Middleware::AcceptLanguage(['gl', 'es', 'en']),
    Middleware::AcceptType(),
    Middleware::AuraRouter($routerContainer)
]);

$response = $dispatcher(ServerRequestFactory::fromGlobals(), new Response());
```

## Available middlewares

### Routers

* **AuraRouter** To execute [Aura.Router](https://github.com/auraphp/Aura.Router) as a middleware. You must use the 3.x version, compatible with psr-7
* **FastRoute** To execute [FastRoute](https://github.com/nikic/FastRoute) as middleware.

### Authentication

* **BasicAuthentication** Implements the basic http authentication.
* **DigestAuthentication** Implements the digest http authentication.

### Client info

* **ClientIp** Detects the client ip(s) and create two attributes in the request instance: `CLIENT_IPS` (array with all ips found) and `CLIENT_IP` (the first ip)
* **AcceptLanguage** Detects the client language using the Accept-Language header and calculate the most preferred language to use. Create two attributes in the request instance: `ACCEPT_LANGUAGE` (array with all languages accepted by the client) and `PREFERRED_LANGUAGE` (the preferred language according with the array of available languages that you can set in the constructor)
* **AcceptType** Detects the client content-type using the Accept header and calculate the most preferred format to use. Create three attributes in the request instance: `ACCEPT_TYPE` (array with all formats accepted by the client), `PREFERRED_TYPE` (the preferred mime-type according with the array of available languages that you can set in the constructor) and `PREFERRED_FORMAT` (the format name, for example: html or json, instead the mime types "text/html", "application/json"). This middleware uses also the path extension to choose the preferred type, for example, if the request uri is "/post/12.json", the preferred format is "json".

### Misc

* **BasePath** Strip off the prefix from the uri path of the request. This is useful if the root of the website is in a subdirectory.

