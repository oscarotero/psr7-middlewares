> **NOTE:** This package is being ported to PSR-15. [Check it out here](https://github.com/middlewares/psr15-middlewares)

# psr7-middlewares


[![Build Status](https://travis-ci.org/oscarotero/psr7-middlewares.svg)](https://travis-ci.org/oscarotero/psr7-middlewares)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/oscarotero/psr7-middlewares/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/oscarotero/psr7-middlewares/?branch=master)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0d91152f-1308-4709-b834-ea048afee7da/big.png)](https://insight.sensiolabs.com/projects/0d91152f-1308-4709-b834-ea048afee7da)

Collection of [PSR-7](http://www.php-fig.org/psr/psr-7/) middlewares.


## Requirements

* PHP >= 5.5
* A [PSR-7 HTTP Message implementation](https://packagist.org/providers/psr/http-message-implementation), for example [zend-diactoros](https://github.com/zendframework/zend-diactoros)
* A PSR-7 middleware dispatcher compatible with the following signature:

```php
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

function (RequestInterface $request, ResponseInterface $response, callable $next) {
    // ...
}
```

So, you can use these midlewares with:

* [Relay](https://github.com/relayphp/Relay.Relay)
* [Expressive](https://docs.zendframework.com/zend-expressive/)
* [Slim 3](http://www.slimframework.com)
* [Spiral](http://spiral-framework.com)
* [Middleman](https://github.com/mindplay-dk/middleman)
* etc...

## Installation

This package is installable and autoloadable via Composer as [oscarotero/psr7-middlewares](https://packagist.org/packages/oscarotero/psr7-middlewares).

```
$ composer require oscarotero/psr7-middlewares
```

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

    //Calculate the response time
    Middleware::responseTime(),

    //Add an Uuid to request
    Middleware::uuid(),

    //Minify the result
    Middleware::minify(),

    //Handle errors
    Middleware::errorHandler()->catchExceptions(true),

    //Override the method using X-Http-Method-Override header
    Middleware::methodOverride(),

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

    //Adds the php debug bar
    Middleware::debugBar(),

    //Execute fast route
    Middleware::fastRoute($app->get('dispatcher')),
]);

$response = $dispatcher(ServerRequestFactory::fromGlobals(), new Response());
```

## Available middlewares

* [AccessLog](#accesslog)
* [AttributeMapper](#attributemapper)
* [AuraRouter](#aurarouter)
* [AuraSession](#aurasession)
* [BasePath](#basepath)
* [BasicAuthentication](#basicauthentication)
* [BlockSpam](#blockspam)
* [Cache](#cache)
* [ClientIp](#clientip)
* [Cors](#cors)
* [Csp](#csp)
* [Csrf](#csrf)
* [DebugBar](#debugbar)
* [Delay](#delay)
* [DetectDevice](#detectdevice)
* [DigestAuthentication](#digestauthentication)
* [EncodingNegotiator](#encodingnegotiator)
* [ErrorHandler](#errorhandler)
* [Expires](#expires)
* [FastRoute](#fastroute)
* [FormTimestamp](#formtimestamp)
* [Firewall](#firewall)
* [FormatNegotiator](#formatnegotiator)
* [Geolocate](#geolocate)
* [GoogleAnalytics](#googleanalytics)
* [Honeypot](#honeypot)
* [Https](#https)
* [ImageTransformer](#imagetransformer)
* [IncludeResponse](#includeresponse)
* [JsonSchema](#jsonschema)
* [LanguageNegotiation](#languagenegotiation)
* [LeagueRoute](#leagueroute)
* [MethodOverride](#methodoverride)
* [Minify](#minify)
* [Payload](#payload)
* [PhpSession](#phpsession)
* [Piwik](#piwik)
* [ReadResponse](#readresponse)
* [Recaptcha](#recaptcha)
* [Rename](#rename)
* [ResponseTime](#responsetime)
* [Robots](#robots)
* [SaveResponse](#saveresponse)
* [Shutdown](#shutdown)
* [TrailingSlash](#trailingslash)
* [Uuid](#uuid)
* [Whoops](#whoops)
* [Www](#www)


### AccessLog

To generate access logs for each request using the [Apache's access log format](https://httpd.apache.org/docs/2.4/logs.html#accesslog). This middleware requires a [Psr log implementation](https://packagist.org/providers/psr/log-implementation), for example [monolog](https://github.com/Seldaek/monolog):

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\AuraRouter;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

//Create the logger
$logger = new Logger('access');
$logger->pushHandler(new ErrorLogHandler());

$middlewares = [

    //Required to get the Ip
    Middleware::ClientIp(),

    Middleware::AccessLog($logger) //Instance of Psr\Log\LoggerInterface
        ->combined(true)           //(optional) To use the Combined Log Format instead the Common Log Format
];
```


### AttributeMapper

Maps middleware specific attribute to regular request attribute under desired name:

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //Example with authentication
    Middleware::BasicAuthentication([
        'username1' => 'password1',
        'username2' => 'password2'
    ]),

    //Map the key used by this middleware
    Middleware::attributeMapper([
        Middleware\BasicAuthentication::KEY => 'auth:username'
    ]),

    function ($request, $response, $next) {
        //We can get the username as usual
        $username = BasicAuthentication::getUsername($request);

        //But also using the "auth:username" attribute name.
        assert($username === $request->getAttribute('auth:username'));

        return $next($request, $response);
    }
];
```

### AuraRouter

To use [Aura.Router (3.x)](https://github.com/auraphp/Aura.Router) as a middleware:

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\AuraRouter;
use Aura\Router\RouterContainer;

//Create the router
$router = new RouterContainer();

$map = $router->getMap();

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
$middlewares = [

    Middleware::AuraRouter($router) //Instance of Aura\Router\RouterContainer
        ->arguments($myApp)         //(optional) append more arguments to the controller
];
```

### AuraSession

Creates a new [Aura.Session](https://github.com/auraphp/Aura.Session) instance with the request.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\AuraSession;

$middlewares = [

    Middleware::AuraSession(),
        ->factory($sessionFactory) //(optional) Intance of Aura\Session\SessionFactory
        ->name('my-session-name'), //(optional) custom session name

    function ($request, $response, $next) {
        //Get the session instance
        $session = AuraSession::getSession($request);

        return $response;
    }
];
```

### BasePath

Removes the prefix from the uri path of the request. This is useful to combine with routers if the root of the website is in a subdirectory. For example, if the root of your website is `/web/public`, a request with the uri `/web/public/post/34` will be converted to `/post/34`. You can provide the prefix to remove or let the middleware autodetect it. In the router you can retrieve the prefix removed or a callable to generate more urls with the base path.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\BasePath;

$middlewares = [

    Middleware::BasePath('/web/public') // (optional) The path to remove...
        ->autodetect(true),             // (optional) ...or/and autodetect the base path

    function ($request, $response, $next) {
        //Get the removed prefix
        $basePath = BasePath::getBasePath($request);

        //Get a callable to generate full paths
        $generator = BasePath::getGenerator($request);

        $generator('/other/path'); // /web/public/other/path

        return $response;
    }
];
```

### BasicAuthentication

Implements the [basic http authentication](http://php.net/manual/en/features.http-auth.php). You have to provide an array with all users and password:

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::BasicAuthentication([
            'username1' => 'password1',
            'username2' => 'password2'
        ])
        ->realm('My realm'), //(optional) change the realm value

    function ($request, $response, $next) {
        $username = BasicAuthentication::getUsername($request);

        return $next($request, $response);
    }
];
```

### BlockSpam

To block referral spam usin the [piwik/referrer-spam-blacklist](https://github.com/piwik/referrer-spam-blacklist) list

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::BlockSpam('spammers.txt'), //(optional) to set a custom spammers list instead the piwik's list
];
```

### Cache

Requires [micheh/psr7-cache](https://github.com/micheh/psr7-cache). Saves the responses' headers in cache and returns a 304 response (Not modified) if the request is cached. It also adds `Cache-Control` and `Last-Modified` headers to the response. You need a cache library compatible with psr-6.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::Cache(new Psr6CachePool()) //the PSR-6 cache implementation
        ->cacheControl('max-age=3600'),    //(optional) to add this Cache-Control header to all responses
];
```

### ClientIp

Detects the client ip(s).

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\ClientIp;

$middlewares = [

    Middleware::ClientIp()
        ->remote()  // (optional) Hack to get the ip from localhost environment
        ->headers([ // (optional) to change the trusted headers
            'Client-Ip',
            'X-Forwarded-For',
            'X-Forwarded'
        ]),

    function ($request, $response, $next) {
        //Get the user ip
        $ip = ClientIp::getIp($request);

        //Get all ips found in the headers
        $all_ips = ClientIp::getIps($request);

        return $next($request, $response);
    }
];
```

### Cors

To use the [neomerx/cors-psr7](https://github.com/neomerx/cors-psr7) library:

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::Cors($settings)                 //(optional) instance of Neomerx\Cors\Contracts\Strategies\SettingsStrategyInterface
        ->origin('http://example.com:123')      //(optional) the server origin
        ->allowedOrigins([                      //(optional) Allowed origins
            'http://good.example.com:321' => true,
            'http://evil.example.com:123' => null,
        ])
        ->allowedMethods([                      //(optional) Allowed methods. The second argument forces to add the allowed methods to preflight response
            'GET' => true,
            'PATCH' => null,
            'POST' => true,
            'PUT' => null,
            'DELETE' => true,
        ], true)
        ->allowedHeaders([                      //(optional) Allowed headers. The second argument forces to add the allowed headers to preflight response
            'content-type' => true,
            'some-disabled-header' => null,
            'x-enabled-custom-header' => true,
        ], true)
        ->exposedHeaders([                      //(optional) Headers other than the simple ones that might be exposed to user agent
            'Content-Type' => true,
            'X-Custom-Header' => true,
            'X-Disabled-Header' => null,
        ])
        ->allowCredentials()                    //(optional) If access with credentials is supported by the resource.
        ->maxAge(0)                             //(optional) Set pre-flight cache max period in seconds.
        ->checkHost(true)                       //(optional) If request 'Host' header should be checked against server's origin.
];
```

### Csp

To use the [paragonie/csp-builder](https://github.com/paragonie/csp-builder) library to add the Content-Security-Policy header to the response.

```php

$middlewares = [

    Middleware::csp($directives)                          //(optional) the array with the directives.
        ->addSource('img-src', 'https://ytimg.com')       //(optional) to add extra sources to whitelist
        ->addDirective('upgrade-insecure-requests', true) //(optional) to add new directives (if it doesn't already exist)
        ->supportOldBrowsers(false)                       //(optional) support old browsers (e.g. safari). True by default
];
```

### Csrf

To add a protection layer agains CSRF (Cross Site Request Forgery). The middleware injects a hidden input with a token in all POST forms and them check whether the token is valid or not. Use `->autoInsert()` to insert automatically the token or, if you prefer, use the generator callable:

```php

$middlewares = [

    //required to save the tokens in the user session
    Middleware::AuraSession(),
    //or
    Middleware::PhpSession(),

    //required to get the format of the request (only executed in html requests)
    Middleware::FormatNegotiator(),

    //required to get the user ip
    Middleware::ClientIp(),

    Middleware::Csrf()
        ->autoInsert(), //(optional) To insert automatically the tokens in all POST forms

    function ($request, $response, $next) {
        //Get a callable to generate tokens (only if autoInsert() is disabled)
        $generator = Middleware\Csrf::getGenerator($request);

        //Use the generator (you must pass the action url)
        $response->getBody()->write(
            '<form action="/action.php" method="POST">'.
            $generator('/action.php').
            '<input type="submit">'.
            '</form>'
        );

        return $next($request, $response);
    }
];
```

### DebugBar

Inserts the [PHP debug bar 1.x](http://phpdebugbar.com/) in the html body. This middleware requires `Middleware::formatNegotiator` executed before, to insert the debug bar only in Html responses.

```php
use Psr7Middlewares\Middleware;
use DebugBar\StandardDebugBar;

$debugBar = new StandardDebugBar();

$middlewares = [

    Middleware::FormatNegotiator(), //(recomended) to insert only in html responses

    Middleware::DebugBar($debugBar) //(optional) Instance of debugbar
        ->captureAjax(true)         //(optional) To send data in headers in ajax
];
```

### Delay

Delays the response to simulate slow bandwidth in local environments. You can use a number or an array to generate random values in seconds.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::delay(3.5),      //delay the response 3.5 seconds

    Middleware::delay([1, 2.5]), //delay the response between 1 and 1.5 seconds
];
```

### DetectDevice

Uses [Mobile-Detect](https://github.com/serbanghita/Mobile-Detect) library to detect the client device.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\DetectDevice;

$middlewares = [

    Middleware::DetectDevice(),

    function ($request, $response, $next) {
        //Get the device info
        $device = DetectDevice::getDevice($request);

        if ($device->isMobile()) {
            //mobile stuff
        }
        elseif ($device->isTablet()) {
            //tablet stuff
        }
        elseif ($device->is('bot')) {
            //bot stuff
        }

        return $next($request, $response);
    },
];
```

### DigestAuthentication

Implements the [digest http authentication](http://php.net/manual/en/features.http-auth.php). You have to provide an array with the users and password:

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::DigestAuthentication([
            'username1' => 'password1',
            'username2' => 'password2'
        ])
        ->realm('My realm') //(optional) custom realm value
        ->nonce(uniqid()),   //(optional) custom nonce value

    function ($request, $response, $next) {
        $username = DigestAuthentication::getUsername($request);

        return $next($request, $response);
    }
];
```

### EncodingNegotiator

Uses [willdurand/Negotiation (2.x)](https://github.com/willdurand/Negotiation) to detect and negotiate the encoding type of the document.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\EncodingNegotiator;

$middlewares = [

    Middleware::EncodingNegotiator()
        ->encodings(['gzip', 'deflate']), //(optional) configure the supported encoding types

    function ($request, $response, $next) {
        //get the encoding (for example: gzip)
        $encoding = EncodingNegotiator::getEncoding($request);

        return $next($request, $response);
    }
];
```

### ErrorHandler

Executes a handler if the response returned by the next middlewares has any error (status code 400-599). You can catch also the exceptions throwed.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\ErrorHandler;

function handler($request, $response, $myApp) {
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

$middlewares = [

    Middleware::ErrorHandler('handler') //(optional) The error handler
        ->arguments($myApp)             //(optional) extra arguments to the handler
        ->catchExceptions()             //(optional) to catch exceptions
        ->statusCode(function ($code) { //(optional) configure which status codes you want to handle with the errorHandler
            return $code >= 400 && $code < 600 && $code != 422; // you can bypass some status codes in case you want to handle it
        })
];
```

### Expires
Adds `Expires` and `max-age` directive of the `Cache-Control` header in the response. It's similar to the apache module [mod_expires](https://httpd.apache.org/docs/current/mod/mod_expires.html). By default uses the same configuration than [h5bp apache configuration](https://github.com/h5bp/server-configs-apache/blob/master/src/web_performance/expires_headers.conf). Useful for static files.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::formatNegotiator(), //(recomended) to detect the content-type header

    Middleware::expires()
        ->addExpire('text/css', '+1 week') //Add or edit the expire of some types
];
```

### FastRoute
To use [FastRoute](https://github.com/nikic/FastRoute) as middleware.

```php
use Psr7Middlewares\Middleware;

$router = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {

    $r->addRoute('GET', '/blog/{id:[0-9]+}', function ($request, $response, $app) {
        return 'This is the post number'.$request->getAttribute('id');
    });
});

$middlewares = [

    Middleware::FastRoute($router) //Instance of FastRoute\Dispatcher
        ->argument($myApp)         //(optional) arguments appended to the controller
];
```

### Firewall

Uses [M6Web/Firewall](https://github.com/M6Web/Firewall) to provide an IP filtering. This middleware deppends of **ClientIp** (to extract the ips from the headers).

[See the ip formats allowed](https://github.com/M6Web/Firewall#entries-formats) for trusted/untrusted options:

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //required to capture the user ips before
    Middleware::ClientIp(),

    //set the firewall
    Middleware::Firewall()
        ->trusted(['123.0.0.*'])   //(optional) ips allowed
        ->untrusted(['123.0.0.1']) //(optional) ips not allowed
];
```

### FormatNegotiator

Uses [willdurand/Negotiation (2.x)](https://github.com/willdurand/Negotiation) to detect and negotiate the format of the document using the url extension and/or the `Accept` http header. It also adds the `Content-Type` header to the response if it's missing.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\FormatNegotiator;

$middlewares = [

    Middleware::FormatNegotiator()
        ->defaultFormat('html') //(optional) default format if it's unable to detect. (by default is "html")
        ->addFormat('tiff', ['image/tiff', 'image/x-tiff']), //(optional) add a new format associated with mimetypes

    function ($request, $response, $next) {
        //get the format (for example: html)
        $format = FormatNegotiator::getFormat($request);

        return $next($request, $response);
    }
];
```

### FormTimestamp

Simple spam protection based on injecting a hidden input in all post forms with the current timestamp. On submit the form, check the time value. If it's less than (for example) 3 seconds ago, assumes it's a bot, so returns a 403 response. You can also set a max number of seconds before the form expires.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //(recomended) to detect html responses
    Middleware::FormatNegotiator(),

    Middleware::FormTimestamp()
        ->key('my-secret-key'),   //Key used to encrypt/decrypt the input value.
        ->min(5)                  //(optional) Minimum seconds needed to validate the request (default: 3)
        ->max(3600)               //(optional) Life of the form in second. Default is 0 (no limit)
        ->inputName('time-token') //(optional) Name of the input (default: hpt_time)
        ->autoInsert(),           //(optional) To insert automatically the inputs in all POST forms

    function ($request, $response, $next) {
        //Get a callable to generate the inputs (only if autoInsert() is disabled)
        $generator = Middleware\FormTimestamp::getGenerator($request);

        //Use the generator (you must pass the action url)
        $response->getBody()->write(
            '<form action="/action.php" method="POST">'.
            $generator().
            '<input type="submit">'.
            '</form>'
        );

        return $next($request, $response);
    }
];
```

### Geolocate

Uses [Geocoder library](https://github.com/geocoder-php/Geocoder) to geolocate the client using the ip. This middleware deppends of **ClientIp** (to extract the ips from the headers).

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\Geolocate;

$middlewares = [

    //(optional) only if you want to save the result in the user session
    Middleware::PhpSession(),
    //or
    Middleware::AuraSession(),


    //required to capture the user ips before
    Middleware::ClientIp(),

    Middleware::Geolocate($geocoder) //(optional) To provide a custom Geocoder instance
        ->saveInSession(),           //(optional) To save the result to reuse in the future requests (required a session middleware before)

    function ($request, $response, $next) {
        //get the location
        $addresses = Geolocate::getLocation($request);

        //get the country
        $country = $addresses->first()->getCountry();

        $response->getBody()->write('Hello to '.$country);

        return $next($request, $response);
    }
];
```

### GoogleAnalytics

Inject the Google Analytics code in all html pages.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //(recomended) to detect html responses
    Middleware::formatNegotiator(),

    Middleware::GoogleAnalytics('UA-XXXXX-X') //The site id
];
```

### Gzip

Use gzip functions to compress the response body, inserting also the `Content-Encoding` header.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //required to get the preferred encoding type
    Middleware::EncodingNegotiator(),

    Middleware::Gzip()
];
```

### Honeypot

Implements a honeypot spam prevention. This technique is based on creating a input field that should be invisible and left empty by real users but filled by most spam bots. The middleware scans the html code and inserts this inputs in all post forms and check in the incoming requests whether this value exists and is empty (is a real user) or doesn't exist or has a value (is a bot) returning a 403 response.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //(recomended) to detect html responses
    Middleware::formatNegotiator(),

    Middleware::Honeypot()
        ->inputName('my_name') //(optional) The name of the input field (by default "hpt_name")
        ->inputClass('hidden') //(optional) The class of the input field (by default "hpt_input")
        ->autoInsert(),        //(optional) To insert automatically the inputs in all POST forms

    function ($request, $response, $next) {
        //Get a callable to generate the inputs (only if autoInsert() is disabled)
        $generator = Middleware\Honeypot::getGenerator($request);

        //Use the generator (you must pass the action url)
        $response->getBody()->write(
            '<form action="/action.php" method="POST">'.
            $generator().
            '<input type="submit">'.
            '</form>'
        );

        return $next($request, $response);
    }
];
```

### Https

Returns a redirection to the https scheme if the request uri is http. It also adds the [Strict Transport Security](https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security) header to protect against protocol downgrade attacks and cookie hijacking.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::Https(true)   //(optional) True to force https, false to force http (true by default)
        ->maxAge(1000000)     //(optional) max-age directive for the Strict-Transport-Security header. By default is 31536000 (1 year)
        ->includeSubdomains() //(optional) To add the "includeSubDomains" attribute to the Strict-Transport-Security header.
];
```

### ImageTransformer

Uses [imagecow/imagecow 2.x](https://github.com/oscarotero/imagecow) to transform images on demand. You can resize, crop, rotate and convert to other formats. Use the [the imagecow syntax](https://github.com/oscarotero/imagecow#execute-multiple-functions) to define the available sizes.

To define the available sizes, you have to asign a filename prefix representing the size, so any file requested with this prefix will be dinamically transformed.

There's also support for [Client hints](https://www.smashingmagazine.com/2016/01/leaner-responsive-images-client-hints/) to avoid to serve images larger than needed (currently supported only in chrome and opera).

If you want to save the transformed images in the cache, provide a library compatible with psr-6 for that.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //(recomended) to detect responses' mimetype
    Middleware::formatNegotiator(),

    Middleware::imageTransformer([   // The available sizes of the images.
            'small.' => 'resizeCrop,50,50', //Creates a 50x50 thumb of any image prefixed with "small." (example: /images/small.avatar.jpg)
            'medium.' => 'resize,500|format,jpg', //Resize the image to 500px and convert to jpg
            'pictures/large.' => 'resize,1000|format,jpg', //Transform only images inside "pictures" directory (example: /images/pcitures/large.avatar.jpg)
        ])
        ->clientHints()              // (optional) To enable the client hints headers
        ->cache(new Psr6CachePool()) // (optional) To save the transformed images in the cache

    function ($request, $response, $next) {
        //Get the generator to generate urls
        $generator = Middleware\ImageTransformer::getGenerator($request);

        //Use the generator
        $response->getBody()->write('<img src="'.$generator('images/picture.jpg', 'small.').'">');

        return $next($request, $response);
    }
];
```

### IncludeResponse

Useful to include old style applications, in which each page has it's own php file. For example, let's say we have an application with paths like `/about-us.php` or `/about-us` (resolved to `/about-us/index.php`), this middleware gets the php file, include it safely, capture the output and the headers send and create a response with the results. If the file does not exits, returns a `404` response (unless `continueOnError` is true).

```php
use Psr7Middlewares\Middleware;

$middlewares = [
    Middleware::includeResponse('/doc/root'), //The path of the document root
        ->continueOnError(true)               // (optional) to continue with the next middleware on error or not
];
```

### JsonValidator

Uses [justinrainbow/json-schema](https://github.com/justinrainbow/json-schema) to validate an `application/json` request body with a JSON schema:

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\JsonValidator;

// Validate using a file:
$middlewares = [
    Middleware::payload(['forceArray' => false]),
    JsonValidator::fromFile(new \SplFileObject(WEB_ROOT . '/json-schema/en.v1.users.json')),
];

// Validate using an array:
$middlewares = [
    Middleware::payload(['forceArray' => false]),
    JsonValidator::fromArray([
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'type' => 'object',
        'properties' => [
            'id' => [
                'type' => 'string'
            ],
        ],
        'required' => [
            'id',
        ]
    ]);
];

// Override the default error handler, which responds with a 422 status code and application/json Content-Type:
$middlewares = [
    Middleware::payload(['forceArray' => false]),
    JsonValidator::fromFile(new \SplFileObject('schema.json'))
        ->setErrorHandler(function ($request, $response, array $errors) {
            $response->getBody()->write('Failed JSON validation.');

            return $response->withStatus(400, 'Oops')
                ->withHeader('Content-Type', 'text/plain');
        }),
];
```

### JsonSchema

Uses [justinrainbow/json-schema](https://github.com/justinrainbow/json-schema) to validate an `application/json` request body using route-matched JSON schemas:

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    // Transform `application/json` into an object, which is a requirement of `justinrainbow/json-schema`.
    Middleware::payload([
        'forceArray' => false,
    ]),

    // Provide a map of route-prefixes to JSON schema files.
    Middleware::jsonSchema([
        '/en/v1/users' => WEB_ROOT . '/json-schema/en.v1.users.json',
        '/en/v1/posts' => WEB_ROOT . '/json-schema/en.v1.posts.json',
        '/en/v2/posts' => WEB_ROOT . '/json-schema/en.v2.posts.json',
    ])
];
```

### LanguageNegotiation

Uses [willdurand/Negotiation](https://github.com/willdurand/Negotiation) to detect and negotiate the client language using the Accept-Language header and (optionally) the uri's path. You must provide an array with all available languages:

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\LanguageNegotiator;

$middlewares = [

    Middleware::LanguageNegotiator(['gl', 'en']) //Available languages
        ->usePath(true)                          //(optional) To search the language in the path: /gl/, /en/
        ->redirect()                             //(optional) To return a redirection if the language is not in the path

    function ($request, $response, $next) {
        //Get the preferred language
        $language = LanguageNegotiator::getLanguage($request);

        return $next($request, $response);
    }
];
```

### LeagueRoute

To use [league/route (2.x)](https://github.com/thephpleague/route) as a middleware:

```php
use Psr7Middlewares\Middleware;
use League\Route\RouteCollection;

$router = new RouteCollection();

$router->get('/blog/{id:[0-9]+}', function ($request, $response, $vars) {
    return 'This is the post number'.$vars['id'];
});

$middlewares = [

    Middleware::LeagueRoute($router) //The RouteCollection instance
];
```

### MethodOverride

Overrides the request method using the `X-Http-Method-Override` header. This is useful for clients unable to send other methods than GET and POST:

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::MethodOverride()
        ->get(['HEAD', 'CONNECT', 'TRACE', 'OPTIONS']), //(optional) to customize the allowed GET overrided methods
        ->post(['PATCH', 'PUT', 'DELETE', 'COPY', 'LOCK', 'UNLOCK']), //(optional) to customize the allowed POST overrided methods
        ->parameter('method-override') //(optional) to use a parsed body and uri query parameter in addition to the header
        ->parameter('method-override', false) //(optional) to use only the parsed body (but not the uri query)
];
```

### Minify

Uses [mrclay/minify](https://github.com/mrclay/minify) to minify the html, css and js code from the responses.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //(recomended) to detect the mimetype of the response
    Middleware::formatNegotiator(),

    Middleware::Minify()
];
```

### Payload

Parses the body of the request if it's not parsed and the method is POST, PUT or DELETE. It has support for json, csv and url encoded format.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::Payload([     // (optional) Array of parsing options:
        'forceArray' => false // Force to use arrays instead objects in json (true by default)
    ])
    ->override(),             // (optional) To override the existing parsed body if exists (false by default)

    function ($request, $response, $next) {
        //Get the parsed body
        $content = $request->getParsedBody();

        return $next($request, $response);
    }
];
```

### PhpSession

Initializes a [php session](http://php.net/manual/en/book.session.php) using the request data.


```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::PhpSession()
        ->name('SessionId') //(optional) Name of the session
        ->id('ABC123')      //(optional) Id of the session

    function ($request, $response, $next) {
        //Use the global $_SESSION variable to get/set data
        $_SESSION['name'] = 'John';

        return $next($request, $response);
    }
];
```

### Piwik

To use the [Piwik](https://piwik.org/) analytics platform. Injects the javascript code just before the `</body>` closing tag.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //(recomended) to detect html responses
    Middleware::formatNegotiator(),

    Middleware::Piwik()
        ->piwikUrl('//example.com/piwik')    // The url of the installed piwik
        ->siteId(1)                          // (optional) The site id (1 by default)
        ->addOption('setDoNotTrack', 'true') // (optional) Add more options to piwik API
];
```

### ReadResponse

Read the response content from a file. It's the opposite of [SaveResponse](#saveresponse). The option `continueOnError` changes the behaviour of the middleware to continue with the next middleware if the response file is NOT found and returns directly the response if the file is found. This is useful to use the middleware as a file based cache and add a router middleware (or other readResponses) next in the queue.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::ReadResponse('path/to/files') // Path where the files are stored
        ->appendQuery(true)                   // (optional) to use the uri query in the filename
        ->continueOnError(true)               // (optional) to continue with the next middleware on error or not
];
```

### Recaptcha

To use the [google recaptcha](https://github.com/google/recaptcha) library for spam prevention.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //required to get the user IP
    Middleware::ClientIp(),

    Middleware::Recapcha('secret') //The secret key
];
```

### Rename

Renames the request path. This is useful in some use cases:

* To rename public paths with random suffixes for security reasons, for example the path `/admin` to a more unpredictible `/admin-19640983`
* Create pretty urls without use any router. For example to access to the path `/static-pages/about-me.php` under the more friendly `/about-me`

Note that the original path wont be publicly accesible. On above examples, requests to `/admin` or `/static-pages/about-me.php` returns 404 responses.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::Rename([
        '/admin' => '/admin-19640983',
    ]),

    function ($request, $response, $next) {
        $path = $request->getUri()->getPath(); // /admin

        return $next($request, $response);
    }
];
```

### ResponseTime

Calculates the response time (in miliseconds) and saves it into `X-Response-Time` header:

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::ResponseTime()
];
```

### Robots

Disables the robots of the search engines for non-production environment. Adds automatically the header `X-Robots-Tag: noindex, nofollow, noarchive` in all responses and returns a default body for `/robots.txt` request.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::Robots(false) //(optional) Set true to allow search engines instead disallow
];
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

$middlewares = [

    Middleware::SaveResponse('path/to/files') //Path directory where save the responses
        ->appendQuery(true)                   // (optional) to append the uri query to the filename
];
```

### Shutdown

Useful to display a 503 maintenance page. You need to specify a handler.

```php
use Psr7Middlewares\Middleware;

function shutdownHandler ($request, $response, $app) {
    $response->getBody()->write('Service unavailable');
}

$middlewares = [

    Middleware::Shutdown('shutdownHandler') //(optional) Callable that generate the response
        ->arguments($app)                   //(optional) to add extra arguments to the handler
];
```

### TrailingSlash

Removes (or adds) the trailing slash of the path. For example, `/post/23/` will be converted to `/post/23`. If the path is `/` it won't be converted. Useful if you have problems with the router.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::TrailingSlash(true) //(optional) set true to add the trailing slash instead remove
        ->redirect(301)             //(optional) to return a 301 (seo friendly) or 302 response to the new path
];
```

### Uuid

Uses [ramsey/uuid (3.x)](https://github.com/ramsey/uuid) to generate an Uuid (Universally Unique Identifiers) for each request (compatible with [RFC 4122](http://tools.ietf.org/html/rfc4122) versions 1, 3, 4 and 5). It's usefull for debugging purposes.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\Uuid;

$middlewares = [

    Middleware::Uuid()
        ->version(4)     //(optional) version of the identifier (1 by default). Versions 3 and 5 need more arguments (see https://github.com/ramsey/uuid#examples)
        ->header(false), //(optional) Name of the header to store the identifier (X-Uuid by default). Set false to don't save header

    function ($request, $response, $next) {
        //Get the X-Uuid header
        $id = $request->getHeaderLine('X-Uuid');

        //Get the Uuid instance
        $uuid = Uuid::getUuid($request);

        echo $uuid->toString();

        return $next($request, $response);
    }
];
```

### Whoops

To use [whoops 2.x](https://github.com/filp/whoops) as error handler.

```php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\Whoops;
use Whoops\Run;

$whoops = new Run();

$middlewares = [

    //(recomended) to allows to choose the best handler according with the response mimetype
    Middleware::formatNegotiator(),

    Middleware::Whoops($whoops) //(optional) provide a custom whoops instance
        ->catchErrors(false)    //(optional) to catch not only exceptions but also php errors (true by default)
];
```

### Www

Adds or removes the `www` subdomain in the host uri and, optionally, returns a redirect response. The following types of host values wont be changed:
* The one word hosts, for example: `http://localhost`.
* The ip based hosts, for example: `http://0.0.0.0`.
* The multi domain hosts, for example: `http://subdomain.example.com`.

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    Middleware::Www(true) //(optional) Add www instead remove it
        ->redirect(301)   //(optional) to return a 301 (seo friendly), 302 response to the new host or false to don't redirect. (301 by default)
];
```

## Lazy/conditional middleware creation

You may want to create middleware in a lazy way under some circunstances:

* The middleware is needed only in a specific context (for example in development environments)
* The middleware creation is expensive and is not needed always (because a previous middleware returns a cached response)
* The middleware is needed only in a specific path

To handle with this, you can use the `Middleware::create()` method that must return a callable or false. Example:

```php
use Psr7Middlewares\Middleware;

$middlewares = [

    //This middleware can return a cached response
    //so the next middleware may not be executed
    Middleware::cache($myPsr6CachePool),

    //Let's say this middleware is expensive, so use a proxy for lazy creation
    Middleware::create(function () use ($app) {
        return Middleware::auraRouter($app->get('router'));
    }),

    //This middleware is needed only in production
    Middleware::create(function () {
        return (getenv('ENV') !== 'production') ? false : Middleware::minify();
    }),

    //This middleware is needed in some cases
    Middleware::create(function ($request, $response) {
        if ($request->hasHeader('Foo')) {
            return Middleware::www();
        }

        return false;
    }),

    //This middleware is needed only in a specific basePath
    Middleware::create('/admin', function () {
        return Middleware::DigestAuthentication(['user' => 'pass']);
    }),

    //This middleware is needed in some cases under a specific basePath
    Middleware::create('/actions', function ($request, $response) {
        if ($request->hasHeader('Foo')) {
            return Middleware::responseTime();
        }

        return false;
    }),
];
```

## Extending middlewares

Some middleware pieces use different functions to change the http messages, depending of some circunstances. For example, [Payload](#payload) parses the raw body content, and the method used depends of the type of the content: it can be json, urlencoded, csv, etc. Other example is the [Minify](#minify) middleware that needs a different minifier for each format (html, css, js, etc), or the [Gzip](#gzip) that depending of the `Accept-Encoding` header, use a different method to compress the response body.

The interface `Psr7Middlewares\Transformers\ResolverInterface` provides a way to resolve and returns the apropiate "transformer" in each case. The transformer is just a callable with a specific signature. You can create custom resolvers or extend the included in this package to add your owns. Let's see an example:

```php
use Psr7Middlewares\Transformers\BodyParser;
use Psr\Http\Message\ServerRequestInterface;

class MyBodyParser extends BodyParser
{
    /**
     * New parser used in request with the format "php"
     */
    public function php(ServerRequestInterface $request)
    {
        $data = unserialize((string) $request->getBody());

        return $request->withParsedBody($data);
    }
}

//Use the resolver
$middlewares = [
    Middleware::Payload()->resolver(new MyBodyParser())
];
```

The following middlewares are using resolvers that you can customize:

* [Payload](#payload) To parse the body according with the format (json, urlencoded, csv, ...)
* [Gzip](#gzip) To encode the body with the encoding method supported by the browser (gzip, deflate)
* [Minify](#minify) To use different minifiers for each format (html, css, js, ...)


## Contribution

New middlewares are appreciated. Just create a pull request.
