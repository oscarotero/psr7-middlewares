<?php

use Psr7Middlewares\Middleware;

class BasePathTest extends Base
{
    public function pathProvider()
    {
        return [
            [
                'http://example.com/project-name/public',
                '/project-name/public',
                '/',
                '/project-name/public/',
            ],
            [
                'http://example.com/project-name/public',
                '/other/path',
                '/project-name/public',
                '/other/path/project-name/public',
            ],
            [
                'http://example.com/project-name/public',
                '/project-name',
                '/public',
                '/project-name/public',
            ],
        ];
    }

    /**
     * @dataProvider pathProvider
     */
    public function testBasePath($uri, $base, $stripped, $full)
    {
        $response = $this->execute(
            [
                Middleware::BasePath($base),
                function ($request, $response, $next) {
                    $generator = Middleware\BasePath::getGenerator($request);

                    $response->getBody()->write(json_encode([
                        'base' => Middleware\BasePath::getBasePath($request),
                        'stripped' => (string) $request->getUri()->getPath(),
                        'full' => $generator($request->getUri()->getPath()),
                    ]));

                    return $response;
                },
            ],
            $uri
        );

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals($base, $body['base']);
        $this->assertEquals($stripped, $body['stripped']);
        $this->assertEquals($full, $body['full']);
    }
}
