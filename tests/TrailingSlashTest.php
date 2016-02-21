<?php

use Psr7Middlewares\Middleware;

class TrailingSlashTest extends Base
{
    public function removeTrailingSlashProvider()
    {
        return [
            ['/foo/bar', '/foo/bar'],
            ['/foo/bar/', '/foo/bar'],
            ['/', '/'],
            ['', '/'],
        ];
    }

    /**
     * @dataProvider removeTrailingSlashProvider
     */
    public function testRemoveTrailingSlash($url, $result)
    {
        $response = $this->execute(
            [
                Middleware::trailingSlash(false),

                function ($request, $response, $next) {
                    $response->getBody()->write((string) $request->getUri());

                    return $response;
                },
            ],
            $url
        );

        $this->assertEquals($result, (string) $response->getBody());
    }

    public function addTrailingSlashProvider()
    {
        return [
            ['/foo/bar', '/foo/bar/'],
            ['/foo/bar/', '/foo/bar/'],
            ['/', '/'],
            ['', '/'],
            ['/index.html', '/index.html'],
            ['/index', '/index/'],
        ];
    }

    /**
     * @dataProvider addTrailingSlashProvider
     */
    public function testAddTrailingSlash($url, $result)
    {
        $response = $this->execute(
            [
                Middleware::trailingSlash(true),

                function ($request, $response, $next) {
                    $response->getBody()->write((string) $request->getUri());

                    return $response;
                },
            ],
            $url
        );

        $this->assertEquals($result, (string) $response->getBody());
    }

    public function testTrailingSlashRedirect()
    {
        $response = $this->execute(
            [
                Middleware::trailingSlash()->redirect(),
            ],
            '/foo/bar/'
        );

        $this->assertEquals(302, (string) $response->getStatusCode());
        $this->assertEquals('/foo/bar', $response->getHeaderLine('location'));
    }
}
