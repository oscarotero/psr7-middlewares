# psr7-middlewares

[![Build Status](https://travis-ci.org/oscarotero/psr7-middlewares.svg)](https://travis-ci.org/oscarotero/psr7-middlewares)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oscarotero/psr7-middlewares/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oscarotero/psr7-middlewares/?branch=master)

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
    Middleware::ExceptionHandler(),
    Middleware::BasePath('/my-site/web'),
    Middleware::DigestAuthentication(['username' => 'password']),
    Middleware::ClientIp(),
    Middleware::Firewall('127.0.0.1'),
    Middleware::LanguageNegotiator(['gl', 'es', 'en']),
    Middleware::FormatNegotiator(),
    Middleware::AuraRouter($routerContainer)
]);

$response = $dispatcher(ServerRequestFactory::fromGlobals(), new Response());
```

## Available middlewares

### AuraRouter

To use [Aura.Router](https://github.com/auraphp/Aura.Router) as a middleware. You must use the 3.x version, compatible with psr-7:

```php
use Aura\Router\RouterContainer;

//Create the router
$routerContainer = new RouterContainer();

$map = $routerContainer->getMap();
$map->get('blog.read', '/blog/{id}', 'blogReadHandler');

//Add to the dispatcher
$dispatcher = new Relay([
    Middleware::AuraRouter($routerContainer)
]);
```

### FastRoute
To use [FastRoute](https://github.com/nikic/FastRoute) as a middleware.

```php
$router = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/blog/{id:[0-9]+}', 'blogReadHandler');
});

$dispatcher = new Relay([
    Middleware::FastRoute($router)
]);
```

### BasicAuthentication

Implements the [basic http authentication](http://php.net/manual/en/features.http-auth.php). It has two arguments:

* users: An array with all users and passwords allowed
* realm: (optional) The realm used in the authentication

```php
$dispatcher = new Relay([
    Middleware::BasicAuthentication([
        'username1' => 'password1',
        'username2' => 'password2'
    ])
]);
```

### DigestAuthentication

Implements the [digest http authentication](http://php.net/manual/en/features.http-auth.php). It has three arguments:

* users: An array with all users and passwords allowed
* realm: (optional) The realm used in the authentication
* nonce: (optional) The nonce value used in the authentication

```php
$dispatcher = new Relay([
    Middleware::DigestAuthentication([
        'username1' => 'password1',
        'username2' => 'password2'
    ])
]);
```

### LanguageNegotiation

Uses the fantastic [willdurand/Negotiation](https://github.com/willdurand/Negotiation) to detect and negotiate the client language. Store the language in the `LANGUAGE` attribute. You must provide an array with all available languages:

```php
$dispatcher = new Relay([
    Middleware::LanguageNegotiation(['gl', 'en', 'es']),

    function ($request, $response, $next) {
        $response->getBody()->write('Your preferred language is '.$request->getAttribute('LANGUAGE'));

        return $next($request, $response);
    }
]);
```

### FormatNegotiation

Uses [willdurand/Negotiation](https://github.com/willdurand/Negotiation) to detect and negotiate the format of the document using the url extension and/or the `Accept` http header. Stores the format in the `FORMAT` attribute. You must provide an array with the format priorities:

```php
$dispatcher = new Relay([
    Middleware::FormatNegotiation(['html', 'json', 'png']),

    function ($request, $response, $next) {
        $response->getBody()->write('You have requested the format '.$request->getAttribute('FORMAT'));

        return $next($request, $response);
    }
]);
```

### ClientIp

Detects the client ip(s) and create two attributes in the request instance: `CLIENT_IPS` (array with all ips found) and `CLIENT_IP` (the first ip)

```php
$dispatcher = new Relay([
    Middleware::ClientIp(),

    function ($request, $response, $next) {
        $ip = $request->getAttribute('CLIENT_IP');
        $all_ips = array_implode(', ', $request->getAttribute('CLIENT_IPS'));

        $response->getBody()->write("Your ip is {$ip} but we also found {$all_ips}";

        return $next($request, $response);
    }
]);
```

### Firewall

Uses [M6Web/Firewall](https://github.com/M6Web/Firewall) to provide a IP filtering. This middleware deppends of **ClientIp** (to extract the ips from the headers). You can provide two arguments:

* trusted: string/array with all ips allowed. [See the ip formats allowed](https://github.com/M6Web/Firewall#entries-formats)
* untrusted: (optional) string/array with the ips not allowed.

```php
$dispatcher = new Relay([
    Middleware::ClientIp(),
    Middleware::Firewall('123.0.0.*')
]);
```

### BasePath

Strip off the prefix from the uri path of the request. This is useful to combine with routers if the root of the website is in a subdirectory. For example, if the root of your website is `/web/public`, a request with the uri `/web/public/post/34` will be converted to `/post/34`.

```php
$dispatcher = new Relay([
    Middleware::BasePath('/web/public'),
]);
```

### ExceptionHandler

Cath any exception throwed by the next middlewares and returns a response with it.

```php
$dispatcher = new Relay([
    Middleware::exceptionHandler(),
]);
```
