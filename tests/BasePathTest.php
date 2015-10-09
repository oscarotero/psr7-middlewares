<?php

use Psr7Middlewares\Middleware;

class BasePathTest extends Base
{
    public function pathProvider()
    {
        return [
            [
                'http://localhost/project-name/public',
                '/project-name/public',
                'http://localhost',
            ],[
                'http://localhost/project-name/public',
                '/other/path',
                'http://localhost/project-name/public',
            ],[
                '/project-name/public',
                '/project-name',
                '/public',
            ],
        ];
    }

    /**
     * @dataProvider pathProvider
     */
    public function testBasePath($url, $basepath, $result)
    {
        $response = $this->execute(
            [
                Middleware::BasePath($basepath),
                function ($request, $response, $next) {
                    $response->getBody()->write((string) $request->getUri());

                    return $response;
                },
            ],
            $url
        );

        $this->assertEquals($result, (string) $response->getBody());
    }
}
