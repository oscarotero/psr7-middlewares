# psr7-middlewares

[![Build Status](https://travis-ci.org/oscarotero/psr7-middlewares.svg)](https://travis-ci.org/oscarotero/psr7-middlewares)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oscarotero/psr7-middlewares/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oscarotero/psr7-middlewares/?branch=master)

Collection of PSR-7 middlewares

## Requirements

* PHP >= 5.5
* A PSR-7 HTTP Message implementation, for example [zend-diactoros](https://github.com/zendframework/zend-diactoros)
* A PSR-7 middleware dispatcher. For example [Relay](https://github.com/relayphp/Relay.Relay) or any other similar.

## Usage example:

```php
use Psr7Middlewares\Middleware;

use Relay\Relay;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Stream;

//Set a stream factory used by some middlewares
Middleware::setStreamFactory(function ($file, $mode) {
    return new Stream($file, $mode);
});

//Create a relay dispatcher and add some middlewares:
$relay = new RelayBuilder();

$dispatcher = $relay->newInstance([
    Middleware::ExceptionHandler(),
    Middleware::Cache('/cache-dir'),
    Middleware::BasePath('/my-site/web'),
    Middleware::DigestAuthentication(['username' => 'password']),
    Middleware::ClientIp(),
    Middleware::Firewall('127.0.0.1'),
    Middleware::LanguageNegotiator(['gl', 'es', 'en']),
    Middleware::FormatNegotiator(),
    Middleware::Minify()
]);

$response = $dispatcher(ServerRequestFactory::fromGlobals(), new Response());
```

## Available middlewares

* [AuraRouter](#aurarouter)
* [AuraSession](#aurasession)
* [BasePath](#basepath)
* [BasicAuthentication](#basicauthentication)
* [Cache](#cache)
* [ClientIp](#clientip)
* [DigestAuthentication](#digestauthentication)
* [ErrorResponseHandler](#errorresponsehandler)
* [ExceptionHandler](#exceptionhandler)
* [FastRoute](#fastroute)
* [Firewall](#firewall)
* [FormatNegotiation](#formatnegotiation)
* [LanguageNegotiation](#languagenegotiation)
* [Minify](#minify)
* [SaveResponse](#saveresponse)
* [TrailingSlash](#trailingslash)

### AuraRouter

To use [Aura.Router](https://github.com/auraphp/Aura.Router) as a middleware. You need to use the 3.x version, compatible with psr-7:

```php
use Aura\Router\RouterContainer;

//Create the router
$routerContainer = new RouterContainer();

$map = $routerContainer->getMap();
$map->get('hello', '/hello/{name}', function ($request, $response, $myApp) {
    //The route values are stored into parameters
    $name = $request->getAttribute('name');

    //You can get also the route instance
    $route = $request->getAttribute('ROUTE');

    //Write directly in the body's response
    $response->getBody()->write('Hello '.$name);

    //or echo the output (it will be captured and passed to body stream)
    echo 'Hello world';

    //or return a string
    return 'Hello world';

    //or return a new response
    return $response->withStatus(200);
});

//Add to the dispatcher
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([
    Middleware::AuraRouter($routerContainer)
        ->arguments($myApp) //to pass more arguments to the controller after request and response
]);
```

### AuraSession

Creates a new [Aura.Session](https://github.com/auraphp/Aura.Session) instance with the request and save it in `SESSION` attribute. You can set an instance of `Aura\Session\SessionFactory` as first argument.

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([
    Middleware::AuraSession(),
        ->name('my-session-name'), //to set a custom session name

    function ($request, $reponse, $next) {
        //Get the session instance
        $session = $request->getAttribute('SESSION');
    }
]);
```

### BasePath

Strip off the prefix from the uri path of the request. This is useful to combine with routers if the root of the website is in a subdirectory. For example, if the root of your website is `/web/public`, a request with the uri `/web/public/post/34` will be converted to `/post/34`.

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([
    Middleware::BasePath('/web/public'),
]);
```

### BasicAuthentication

Implements the [basic http authentication](http://php.net/manual/en/features.http-auth.php). You have to pass an array with all users and password allowed as first argument:

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([

    //set the names and values as first argument
    Middleware::BasicAuthentication([
            'username1' => 'password1',
            'username2' => 'password2'
        ])
        ->realm('My realm') //change the realm value
]);
```

### Cache

To save and reuse responses based in the Cache-Control: max-age directive and Expires header.

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([

    //set the directory path where to store the cached responses
    Middleware::CacheMaxAge('cache/responses')

    function($request, $response, $next) {
        //Cache the response 1 hour
        return $response->withHeader('Cache-Control', 'max-age=3600');
    }
]);
```

### ClientIp

Detects the client ip(s) and create two attributes in the request instance: `CLIENT_IPS` (array with all ips found) and `CLIENT_IP` (the first ip). You can set an array of allowed headers as the first argument.

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([
    Middleware::ClientIp(),

    function ($request, $response, $next) {
        //Get the user ip
        $ip = $request->getAttribute('CLIENT_IP');

        //Get all ips found in the headers
        $all_ips = array_implode(', ', $request->getAttribute('CLIENT_IPS'));

        return $next($request, $response);
    }
]);
```

### DigestAuthentication

Implements the [digest http authentication](http://php.net/manual/en/features.http-auth.php). You have to pass an array with all users and password allowed as first argument:

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([

    //set the names and values as first argument
    Middleware::DigestAuthentication([
            'username1' => 'password1',
            'username2' => 'password2'
        ])
        ->realm('My realm') //to customice the realm value
        ->nonce(uniqid()) //to customice the nonce value
]);
```

### ErrorResponseHandler

Execute a handler if the response returned by the next middlewares has any error (status code 400-599). It also catch any exception and handle it as an error 500.

```php
function errorHandler($request, $response, $myApp) {
    switch ($response->getStatusCode()) {
        case 404:
            return 'Page not found';

        case 500:
            //you can get the exception catched
            $exception = $request->getAttribute('EXCEPTION');

            return 'Server error: '.$exception->getMessage();

        default:
            return 'There was an error'
    }
}

$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([

    //Set the callable function as the first argument
    Middleware::ErrorResponseHandler('errorHandler')
        ->arguments($myApp) //extra arguments passed to the callable
]);
```

### ExceptionHandler

Cath any exception throwed by the next middlewares and returns a response with it. You have to pass a callable that returns an instance of Stream:

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([
    Middleware::exceptionHandler();
]);
```

### FastRoute
To use [FastRoute](https://github.com/nikic/FastRoute) as a middleware.

```php
$router = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/blog/{id:[0-9]+}', 'blogReadHandler');
});

$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([

    //pass the router as first argument
    Middleware::FastRoute($router)
        ->argument($myApp) //extra arguments passed to the controller
]);
```

### Firewall

Uses [M6Web/Firewall](https://github.com/M6Web/Firewall) to provide a IP filtering. This middleware deppends of **ClientIp** (to extract the ips from the headers).

[See the ip formats allowed](https://github.com/M6Web/Firewall#entries-formats) for trusted/untrusted options:

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([

    //needed to capture the user ips
    Middleware::ClientIp(),

    //set the firewall
    Middleware::Firewall()
        ->trusted('123.0.0.*') //ips allowed
        ->untrusted('123.0.0.1') //ips not allowed
]);
```

### FormatNegotiation

Uses [willdurand/Negotiation](https://github.com/willdurand/Negotiation) to detect and negotiate the format of the document using the url extension and/or the `Accept` http header. Stores the format in the `FORMAT` attribute. The middleware add also the `Content-Type` header to the response if it's missing. You can pass an instance of `Negotiation\FormatNegotiator` as first argument.

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([

    Middleware::FormatNegotiation()
        ->addFormat('pdf', ['application/pdf', 'application/x-download']) //add new formats and mimetypes associated
    }
]);
```

### LanguageNegotiation

Uses [willdurand/Negotiation](https://github.com/willdurand/Negotiation) to detect and negotiate the client language. Store the language in the `LANGUAGE` attribute. You must provide an array with all available languages:

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([

    //Set all available languages as first argument
    Middleware::LanguageNegotiation(['gl', 'en', 'es']),

    function ($request, $response, $next) {
        //Get the preferred language
        $language = $request->getAttribute('LANGUAGE');

        return $next($request, $response);
    }
]);
```

### Minify

Uses [mrclay/minify](https://github.com/mrclay/minify) to minify the html, css and js code from the responses.

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([
    
    Middleware::Minify()
        ->forCache(true) //only save cacheable responses
        ->inlineCss(false) //enable/disable inline css minification
        ->inlineJs(false) //enable/disable inline js minification
]);
```

### SaveResponse

Saves the response content into a file if all of the following conditions are met:

* The status code is `200`
* The `Cache-Control` header does not contain `no-cache` value
* The request has not query parameters.

This is useful for cache purposes

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([
    Middleware::SaveResponse('path/to/document/root')
        ->basePath('public') //optional basepath ignored from the request uri
]);
```

### TrailingSlash

Removes the trailing slash of the path. Useful if you have problems with the router

```php
$relay = new RelayBuilder();

$dispatcher = $relay->getInstance([
    Middleware::TrailingSlash()
]);
```

