<?php

use Psr7Middlewares\Middleware;

class LazyLoadTest extends Base
{
    public function testCreate()
    {
        $response = $this->execute(
            [
                Middleware::create(function () {
                    return Middleware::BasePath('/project-name/public');
                }),

                function ($request, $response, $next) {
                    $response->getBody()->write((string) $request->getUri());

                    return $response;
                },
            ],
            'http://localhost/project-name/public/hello'
        );

        $this->assertEquals('http://localhost/hello', (string) $response->getBody());
    }

    public function conditionalDataProvider()
    {
        return [
            [false, 'Foo'],
            [true, 'Bar,Foo'],
        ];
    }

    /**
     * @dataProvider conditionalDataProvider
     */
    public function testConditionalCreate($condition, $result)
    {
        $response = $this->execute(
            [
                Middleware::create(function () use ($condition) {
                    if (!$condition) {
                        return false;
                    }

                    return function ($request, $response, $next) {
                        return $next($request, $response->withHeader('X-Foo', 'Bar'));
                    };
                }),
                function ($request, $response, $next) {
                    return $next($request, $response->withAddedHeader('X-Foo', 'Foo'));
                },
            ]
        );

        $this->assertSame($result, $response->getHeaderLine('X-Foo'));
    }

    public function createWithBasePathProvider()
    {
        return [
            ['/one', 'http://example.com/one', true],
            ['/one', 'http://example.com/onetwo', false],
            ['/two', 'http://example.com/one', false],
            ['', 'http://example.com/one', true],
            ['/', 'http://example.com/one', true],
            ['/one/', 'http://example.com/one', true],
        ];
    }

    /**
     * @dataProvider createWithBasePathProvider
     */
    public function testCreateWithBasePath($basePath, $url, $passed)
    {
        $response = $this->execute(
            [
                Middleware::create($basePath, function () {
                    return Middleware::responseTime();
                }),

                function ($request, $response, $next) {
                    $response->getBody()->write((string) $request->getUri());

                    return $response;
                },
            ],
            $url
        );

        $this->assertEquals($url, (string) $response->getBody());

        if ($passed) {
            $this->assertTrue($response->hasHeader('X-Response-Time'));
        } else {
            $this->assertFalse($response->hasHeader('X-Response-Time'));
        }
    }
}
