<?php

use Psr7Middlewares\Middleware;

class RenameTest extends Base
{
    public function pathProvider()
    {
        return [
            ['/admin', '', 404],
            ['/admin-123', '/admin', 200],
            ['', '', 200],
        ];
    }

    /**
     * @dataProvider pathProvider
     */
    public function testBasePath($url, $body, $code)
    {
        $response = $this->execute(
            [
                Middleware::Rename([
                    '/admin' => '/admin-123',
                ]),
                function ($request, $response, $next) {
                    $response->getBody()->write((string) $request->getUri());

                    return $response;
                },
            ],
            $url
        );

        $this->assertEquals($body, (string) $response->getBody());
        $this->assertEquals($code, $response->getStatusCode());
    }
}
