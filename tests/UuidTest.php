<?php

use Psr7Middlewares\Middleware;
use Ramsey\Uuid\Uuid;

class UuidTest extends Base
{
    public function testUuid1()
    {
        $response = $this->execute(
            [
                Middleware::Uuid(),
                function ($request, $response, $next) {
                    $this->assertRegExp('/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/', $request->getHeaderLine('X-Uuid'));
                    return $next($request, $response);
                },
            ]
        );
    }

    public function testUuid3()
    {
        $response = $this->execute(
            [
                Middleware::Uuid(3, Uuid::NAMESPACE_DNS, 'oscarotero.com'),
                function ($request, $response, $next) {
                    $this->assertRegExp('/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/', $request->getHeaderLine('X-Uuid'));
                    return $next($request, $response);
                },
            ]
        );
    }

    public function testUuid4()
    {
        $response = $this->execute(
            [
                Middleware::Uuid(4),
                function ($request, $response, $next) {
                    $this->assertRegExp('/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/', $request->getHeaderLine('X-Uuid'));
                    return $next($request, $response);
                },
            ]
        );
    }

    public function testUuid5()
    {
        $response = $this->execute(
            [
                Middleware::Uuid(5, Uuid::NAMESPACE_DNS, 'oscarotero.com'),
                function ($request, $response, $next) {
                    $this->assertRegExp('/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/', $request->getHeaderLine('X-Uuid'));
                    return $next($request, $response);
                },
            ]
        );
    }
}
