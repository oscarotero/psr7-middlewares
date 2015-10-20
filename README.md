# psr7-middlewares

[![Build Status](https://travis-ci.org/oscarotero/psr7-middlewares.svg)](https://travis-ci.org/oscarotero/psr7-middlewares)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oscarotero/psr7-middlewares/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oscarotero/psr7-middlewares/?branch=master)

Collection of PSR-7 middlewares

## Requirements

* PHP >= 5.5
* A PSR-7 HTTP Message implementation, for example [zend-diactoros](https://github.com/zendframework/zend-diactoros)
* A PSR-7 middleware dispatcher. For example [Relay](https://github.com/relayphp/Relay.Relay) or any other compatible.

## Usage example:

```php
use Psr7Middlewares\Middleware;

use Relay\RelayBuilder;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Stream;

//Set a stream factory used by some middlewares
//(Required only if Zend\Diactoros\Stream is not detected)
Middleware::setStreamFactory(function ($file, $mode) {
    return new Stream($file, $mode);
});

//Create a relay dispatcher and add some middlewares:
$relay = new RelayBuilder();

$dispatcher = $relay->newInstance([

    //Add an Uuid to request
    Middleware::uuid(),
    
    //Handle errors
    Middleware::errorHandler('error_handler_function')->catchExceptions(true),

    //Override the method using X-Http-Method-Override header
    Middleware::methodOverride(),

    //Block search engines robots indexing
    Middleware::robots(),

    //Parse the request payload
    Middleware::payload(),

    //Remove the path prefix
    Middleware::basePath('/my-site/web'),

    //Remove the trailing slash
    Middleware::trailingSlash(),

    //Digest authentication
    Middleware::digestAuthentication(['username' => 'password']),

    //Get the client ip
    Middleware::clientIp(),

    //Allow only some ips
    Middleware::firewall(['127.0.0.*']),

    //Detects the user preferred language
    Middleware::languageNegotiator(['gl', 'es', 'en']),

    //Detects the format
    Middleware::formatNegotiator(),

    //Execute fast route
    Middleware::fastRoute($app->get('dispatcher')),

    //Minify the result
    Middleware::minify()

    //Saves the response in a file
    Middleware::saveResponse('app/public')
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
* [Cors](#cors)
* [DigestAuthentication](#digestauthentication)
* [ErrorHandler](#errorhandler)
* [FastRoute](#fastroute)
* [Firewall](#firewall)
* [FormatNegotiation](#formatnegotiation)
* [LanguageNegotiation](#languagenegotiation)
* [MethodOverride](#methodoverride)
* [Minify](#minify)
* [Payload](#payload)
* [ReadResponse](#readresponse)
* [Robots](#robots)
* [SaveResponse](#saveresponse)
* [Shutdown](#shutdown)
* [TrailingSlash](#trailingslash)
* [Uuid](#uuid)

### AuraRouter

To use [Aura.Router](https://github.com/auraphp/Aura.Router) as a middleware. You need the 3.x version, compatible with psr-7:

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\AuraRouter;
use Aura\Router\RouterContainer;

//Create the router
$routerContainer = new RouterContainer();

$map = $routerContainer->getMap();

$map->get('hello', '/hello/{name}', function ($request, $response, $myApp) {

    //The route parameters are stored as attributes
    $name = $request->getAttribute('name');

    //You can get also the route instance
    $route = AuraRouter::getRoute($request);

    //Write directly in the response's body
    $response->getBody()->write('Hello '.$name);

    //or echo the output (it will be captured and writted into body)
    echo 'Hello world';

    //or return a string
    return 'Hello world';

    //or return a new response
    return $response->withStatus(200);
});

//Add to the dispatcher
$dispatcher = $relay->getInstance([

    Middleware::AuraRouter()
        ->router($routerContainer) //Instance of Aura\Router\RouterContainer
        ->arguments($myApp) //(optional) append more arguments to the controller
]);
```

### AuraSession

Creates a new [Aura.Session](https://github.com/auraphp/Aura.Session) instance with the request.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\AuraSession;

$dispatcher = $relay->getInstance([

    Middleware::AuraSession(),
        ->factory($sessionFactory) //(optional) Intance of Aura\Session\SessionFactory
        ->name('my-session-name'), //(optional) custom session name

    function ($request, $reponse, $next) {
        //Get the session instance
        $session = AuraSession::getSession($request);
    }
]);
```

### BasePath

Strip off the prefix from the uri path of the request. This is useful to combine with routers if the root of the website is in a subdirectory. For example, if the root of your website is `/web/public`, a request with the uri `/web/public/post/34` will be converted to `/post/34`.

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([
    Middleware::BasePath('/web/public'),
]);
```

### BasicAuthentication

Implements the [basic http authentication](http://php.net/manual/en/features.http-auth.php). You have to provide an array with all users and password:

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([

    Middleware::BasicAuthentication()
        ->users([
            'username1' => 'password1',
            'username2' => 'password2'
        ])
        ->realm('My realm') //(optional) change the realm value
]);
```

### Cache

To save and reuse responses based in the Cache-Control: max-age directive and Expires header. You need a cache library compatible with psr-6

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([

    Middleware::Cache()
        ->cache(new Psr6CachePool()) //the psr-6 cache implementation

    function($request, $response, $next) {
        //Cache the response 1 hour
        return $response->withHeader('Cache-Control', 'max-age=3600');
    }
]);
```

### ClientIp

Detects the client ip(s).

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\ClientIp;

$dispatcher = $relay->getInstance([

    Middleware::ClientIp()
        ->headers([
            'Client-Ip',
            'X-Forwarded-For',
            'X-Forwarded'
        ]), //(optional) to change the trusted headers

    function ($request, $response, $next) {
        //Get the user ip
        $ip = ClientIp::getIp($request);

        //Get all ips found in the headers
        $all_ips = ClientIp::getIps($request);

        return $next($request, $response);
    }
]);
```

### Cors

To use the [neomerx/cors-psr7](https://github.com/neomerx/cors-psr7) library:

```php
use Neomerx\Cors\Strategies\Settings

$relay = new RelayBuilder();

$settings = (new Settings())
    ->setServerOrigin([
        'scheme' => 'http',
        'host'   => 'example.com',
        'port'   => '123',
    ]);

$dispatcher = $relay->getInstance([

    Middleware::Cors()
        ->settings($settings)
]);
```

### DigestAuthentication

Implements the [digest http authentication](http://php.net/manual/en/features.http-auth.php). You have to provide an array with the users and password:

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([

    Middleware::DigestAuthentication()
        ->users([
            'username1' => 'password1',
            'username2' => 'password2'
        ])
        ->realm('My realm') //(optional) custom realm value
        ->nonce(uniqid()) //(optional) custom nonce value
]);
```

### ErrorHandler

Executes a handler if the response returned by the next middlewares has any error (status code 400-599). You can catch also the exceptions throwed or even use [whoops](https://github.com/filp/whoops) as error handler.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\ErrorHandler;

function errorHandler($request, $response, $myApp) {
    switch ($response->getStatusCode()) {
        case 404:
            return 'Page not found';

        case 500:
            //you can get the exception catched
            $exception = ErrorHandler::getException($request);

            return 'Server error: '.$exception->getMessage();

        default:
            return 'There was an error'
    }
}

$whoops = new Whoops\Run();

$dispatcher = $relay->getInstance([

    Middleware::ErrorHandler()
        //My error handler
        ->handler('errorHandler')

        //(optional) append arguments to the handler
        ->arguments($myApp)

        //(optional) provide a whoops instance to capture errors and exceptions
        ->whoops($whoops)

        //(optional) unregister the error catcher
        ->after(function ($handler) use ($whoops) {
            $whoops->popHandler();
        })

        //(optional) catch exceptions, if you don't use an external library for that
        ->catchExceptions(true)
]);
```

### FastRoute
To use [FastRoute](https://github.com/nikic/FastRoute) as a middleware.

```php
use Psr7Middlewares\Middleware;

$router = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {

    $r->addRoute('GET', '/blog/{id:[0-9]+}', function ($request, $response, $app) {
        return 'This is the post number'.$request->getAttribute('id');
    });
});

$dispatcher = $relay->getInstance([

    Middleware::FastRoute()
        ->router($router) //Instance of FastRoute\Dispatcher
        ->argument($myApp) //(optional) arguments appended to the controller
]);
```

### Firewall

Uses [M6Web/Firewall](https://github.com/M6Web/Firewall) to provide a IP filtering. This middleware deppends of **ClientIp** (to extract the ips from the headers).

[See the ip formats allowed](https://github.com/M6Web/Firewall#entries-formats) for trusted/untrusted options:

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([

    //needed to capture the user ips before
    Middleware::ClientIp(),

    //set the firewall
    Middleware::Firewall()
        ->trusted(['123.0.0.*']) //(optional) ips allowed
        ->untrusted(['123.0.0.1']) //(optional) ips not allowed
]);
```

### FormatNegotiation

Uses [willdurand/Negotiation (2.x)](https://github.com/willdurand/Negotiation) to detect and negotiate the format of the document using the url extension and/or the `Accept` http header. It also adds the `Content-Type` header to the response if it's missing.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\FormatNegotiation;

$dispatcher = $relay->getInstance([

    Middleware::FormatNegotiation()
        ->defaultFormat('html') //(optional) default format if it's unable to detect. (by default is "html")
        ->addFormat('pdf', ['application/pdf', 'application/x-download']) //(optional) add new formats and mimetypes
    },

    function ($request, $response, $next) {
        //get the format (for example: html)
        $format = FormatNegotiation::getFormat($request);

        return $next($request, $response);
    }
]);
```

### LanguageNegotiation

Uses [willdurand/Negotiation](https://github.com/willdurand/Negotiation) to detect and negotiate the client language. You must provide an array with all available languages:

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\LanguageNegotiation;

$dispatcher = $relay->getInstance([

    Middleware::LanguageNegotiation()
        ->languages(['gl', 'en', 'es']), //Available languages

    function ($request, $response, $next) {
        //Get the preferred language
        $language = LanguageNegotiation::getLanguage($request);

        return $next($request, $response);
    }
]);
```

### MethodOverride

Overrides the request method using the `X-Http-Method-Override` header. This is useful for clients unable to send other methods than GET and POST:

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([

    Middleware::MethodOverride()
        ->get(['HEAD', 'CONNECT', 'TRACE', 'OPTIONS']), //(optional) to customize the allowed GET overrided methods
        ->post(['PATCH', 'PUT', 'DELETE', 'COPY', 'LOCK', 'UNLOCK']), //(optional) to customize the allowed POST overrided methods
]);
```

### Minify

Uses [mrclay/minify](https://github.com/mrclay/minify) to minify the html, css and js code from the responses.

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([
    
    Middleware::Minify()
        ->forCache(true) //(optional) only minify cacheable responses (see SaveResponse)
        ->inlineCss(false) //(optional) enable/disable inline css minification
        ->inlineJs(false) //(optional) enable/disable inline js minification
]);
```

### Payload

Parses the body of the request if it's not parsed and the method is POST, PUT or DELETE. It has support for json, csv and url encoded format.

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([
    
    Middleware::Payload()
        ->associative(true) //(optional) To generate associative arrays with json objects

    function ($request, $response, $next) {
        //Get the parsed body
        $content = $request->getParsedBody();

        return $next($request, $response);
    }
]);
```

### ReadResponse

Read the response content from a file. It's the opposite of [SaveResponse](#saveresponse)

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([

    Middleware::ReadResponse()
        ->storage('path/to/document/root') //Path where the files are stored
        ->basePath('public') //(optional) basepath ignored from the request uri
]);
```

### Robots

Disables the robots of the search engines for non-production environment. Adds automatically the header `X-Robots-Tag: noindex, nofollow, noarchive` in all responses and returns a default body for `/robots.txt` request.

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([

    Middleware::Robots()
]);
```

### SaveResponse

Saves the response content into a file if all of the following conditions are met:

* The method is `GET`
* The status code is `200`
* The `Cache-Control` header does not contain `no-cache` value
* The request has not query parameters.

This is useful for cache purposes

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([

    Middleware::SaveResponse()
        ->storage('path/to/document/root') //Path directory where save the responses
        ->basePath('public') //(optional) basepath ignored from the request uri
]);
```

### Shutdown

Useful to display a 503 maintenance page. You need to specify a handler.

```php
use Psr7Middlewares\Middleware;

function shutdownHandler ($request, $response, $app) {
    $response->getBody()->write('Service unavailable');
}

$dispatcher = $relay->getInstance([

    Middleware::Shutdown()
        ->handler('shutdownHandler') // Callable that generate the response
        ->arguments($app) //(optional) to add extra arguments to the handler
]);
```

### TrailingSlash

Removes (or adds) the trailing slash of the path. For example, `/post/23/` will be converted to `/post/23`. If the path is `/` it won't be converted. Useful if you have problems with the router.

```php
use Psr7Middlewares\Middleware;

$dispatcher = $relay->getInstance([

    Middleware::TrailingSlash()
        ->addSlash(true) //(optional) to add the trailing slash instead remove
        ->basePath('public') //(optional) basepath
]);
```

### Uuid

Uses [ramsey/uuid](https://github.com/ramsey/uuid) to generate an Uuid (Universally Unique Identifiers) for each request (compatible with [RFC 4122](http://tools.ietf.org/html/rfc4122) versions 1, 3, 4 and 5). It's usefull for debugging purposes.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\Uuid;

$dispatcher = $relay->getInstance([

    Middleware::Uuid()
        ->version(4) //(optional) version of the identifier (1 by default). Versions 3 and 5 need more arguments (see https://github.com/ramsey/uuid#examples)
        ->header(false), //(optional) Name of the header to store the identifier (X-Uuid by default). Set false to don't save header

    function ($request, $response, $next) {
        //Get the X-Uuid header
        $id = $request->getHeaderLine('X-Uuid');

        //Get the Uuid instance
        $uuid = Uuid::getUuid($request);

        echo $uuid->toString();

        return $next($request, $response);
    }
]);
```

## Contribution

New middlewares are appreciated. Just create a pull request.
