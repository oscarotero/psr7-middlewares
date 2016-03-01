<?php

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils;

class SessionStorageTest extends Base
{
    public function testSessionStorage()
    {
        $response = $this->execute(
            [
                Middleware::PhpSession(),
                new Middleware1(),
                new Middleware2(),
            ]
        );

        $this->assertEquals('Hello', (string) $response->getBody());
    }

    public function testAuraSessionStorage()
    {
        $response = $this->execute(
            [
                Middleware::AuraSession(),
                new Middleware1(),
                new Middleware2(),
            ]
        );

        $this->assertEquals('Hello', (string) $response->getBody());
    }
}

class Middleware1
{
    use Utils\StorageTrait;

    public function __invoke($request, $response, $next)
    {
        $response = $next($request, $response);
        $storage = self::getStorage($request, 'test');

        $response->getBody()->write($storage['value']);

        return $response;
    }
}

class Middleware2
{
    use Utils\StorageTrait;

    public function __invoke($request, $response, $next)
    {
        $storage = &self::getStorage($request, 'test');
        $storage['value'] = 'Hello';

        return $next($request, $response);
    }
}
