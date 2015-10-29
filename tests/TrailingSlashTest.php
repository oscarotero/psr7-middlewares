<?php

use Psr7Middlewares\Middleware;

class TrailingSlashTest extends Base
{
    public function pathsProvider()
    {
        return [
            ['/foo/bar', '/foo/bar', ''],
            ['/foo/bar/', '/foo/bar', '/'],
            ['/', '/', '/'],
            ['', '/', '/'],
            ['/www/public', '/www/public/', '/www/public'],
        ];
    }

    /**
     * @dataProvider pathsProvider
     */
    public function testTrailingSlash($url, $result, $basePath)
    {
        $response = $this->execute(
            [
                Middleware::trailingSlash()
                    ->basePath($basePath),

                function ($request, $response, $next) {
                    $response->getBody()->write($request->getUri()->getPath());

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
