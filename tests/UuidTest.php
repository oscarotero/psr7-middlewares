<?php

use Psr7Middlewares\Middleware;
use Ramsey\Uuid\Uuid;

class UuidTest extends Base
{
    const REGEX = '/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/';

    public function testUuid1()
    {
        $response = $this->execute(
            [
                Middleware::Uuid(),
                function ($request, $response, $next) {
                    $response->getBody()->write($request->getHeaderLine('X-Uuid'));

                    return $next($request, $response);
                },
            ]
        );

        $this->assertRegExp(self::REGEX, $response->getHeaderLine('X-Uuid'));
        $this->assertEquals($response->getHeaderLine('X-Uuid'), (string) $response->getBody());
    }

    public function testUuid3()
    {
        $response = $this->execute(
            [
                Middleware::Uuid(3, Uuid::NAMESPACE_DNS, 'oscarotero.com'),
                function ($request, $response, $next) {
                    $response->getBody()->write($request->getHeaderLine('X-Uuid'));

                    return $next($request, $response);
                },
            ]
        );

        $this->assertRegExp(self::REGEX, $response->getHeaderLine('X-Uuid'));
        $this->assertEquals($response->getHeaderLine('X-Uuid'), (string) $response->getBody());
    }

    public function testUuid4()
    {
        $response = $this->execute(
            [
                Middleware::Uuid(4),
                function ($request, $response, $next) {
                    $response->getBody()->write($request->getHeaderLine('X-Uuid'));

                    return $next($request, $response);
                },
            ]
        );

        $this->assertRegExp(self::REGEX, $response->getHeaderLine('X-Uuid'));
        $this->assertEquals($response->getHeaderLine('X-Uuid'), (string) $response->getBody());
    }

    public function testUuid5()
    {
        $response = $this->execute(
            [
                Middleware::Uuid(5, Uuid::NAMESPACE_DNS, 'oscarotero.com'),
                function ($request, $response, $next) {
                    $response->getBody()->write($request->getHeaderLine('X-Uuid'));

                    return $next($request, $response);
                },
            ]
        );

        $this->assertRegExp(self::REGEX, $response->getHeaderLine('X-Uuid'));
        $this->assertEquals($response->getHeaderLine('X-Uuid'), (string) $response->getBody());
    }
}
