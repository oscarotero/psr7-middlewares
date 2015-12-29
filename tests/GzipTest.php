<?php

use Psr7Middlewares\Middleware;

class GzipTest extends Base
{
    public function testGzip()
    {
        $response = $this->execute(
            [
                Middleware::EncodingNegotiator(),
                function ($request, $response, $next) {
                    $response->getBody()->write('Hello world');
                    return $next($request, $response);
                },
                Middleware::Gzip(),
            ],
            '',
            ['Accept-Encoding' => 'gzip, deflate']
        );

        $this->assertEquals('gzip', $response->getHeaderLine('Content-Encoding'));
        $this->assertEquals(gzencode('Hello world'), (string) $response->getBody());
    }
}
