<?php

use Psr7Middlewares\Middleware;

class ExpiresTest extends Base
{
    public function expiresProvider()
    {
        return [
            [
                'styles.css',
                '',
                'max-age='.(strtotime('+1 year') - time()),
            ],
            [
                'styles.css',
                'no-store',
                'no-store max-age='.(strtotime('+1 year') - time()),
            ],
        ];
    }

    /**
     * @dataProvider expiresProvider
     */
    public function testBasePath($uri, $header, $result)
    {
        $response = $this->execute(
            [
                Middleware::formatNegotiator(),
                Middleware::expires(),
                function ($request, $response, $next) use ($header) {
                    return $next($request, $response->withHeader('Cache-Control', $header));
                },
            ],
            $uri
        );

        $this->assertEquals($result, $response->getHeaderLine('Cache-Control'));
        $this->assertTrue($response->hasHeader('Expires'));
    }
}
