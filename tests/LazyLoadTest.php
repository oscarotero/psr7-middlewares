<?php

use Psr7Middlewares\Middleware;

class LazyLoadTest extends Base
{
    public function testLazyLoad()
    {
        $response = $this->execute(
            [
                Middleware::middleware(function () {
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
}
