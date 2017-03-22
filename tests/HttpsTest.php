<?php

use Psr7Middlewares\Middleware;

class HttpsTest extends Base
{
    public function HttpsProvider()
    {
        return [
            [true, 'http://localhost', true, 301, 'https://localhost', ''],
            [true, 'https://localhost', false, 200, '', 'max-age=31536000'],
            [true, 'https://localhost', true, 200, '', 'max-age=31536000;includeSubDomains'],
            [false, 'http://localhost', true, 200, '', ''],
            [false, 'https://localhost', true, 301, 'http://localhost', ''],
        ];
    }

    /**
     * @dataProvider HttpsProvider
     */
    public function testHttps($add, $url, $includeSubdomains, $status, $location, $hsts)
    {
        $response = $this->execute(
            [
                Middleware::Https($add)->includeSubdomains($includeSubdomains),
            ],
            $url
        );

        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($location, $response->getHeaderLine('Location'));
        $this->assertEquals($hsts, $response->getHeaderLine('Strict-Transport-Security'));
    }

    public function testRedirectSchemeMatchesPort()
    {
        $url = 'http://domain.com:80';

        $response = $this->execute(
            [
                Middleware::Https()->includeSubdomains(false),
            ],
            $url
        );
        $expectedLocation = 'https://domain.com';
        $location = $response->getHeaderLine('Location');
        $this->assertEquals($expectedLocation, $location);
    }

    public function testCheckHttpsForward()
    {
        $url = 'http://domain.com:80';

        $response = $this->execute(
            [
                Middleware::Https()
                    ->includeSubdomains(false)
                    ->checkHttpsForward(true),
            ],
            $url,
            ['X-Forwarded-Proto' => 'https']
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRedirectScheme()
    {
        $url = 'https://domain.com';

        $response = $this->execute(
            [
                Middleware::Https()
                    ->includeSubdomains(false),
                function ($request, $response, $next) {
                    return $next($request, $response->withStatus(301)->withHeader('Location', 'http://domain.com/index'));
                },
            ],
            $url
        );

        $expectedLocation = 'https://domain.com/index';
        $location = $response->getHeaderLine('Location');
        $this->assertEquals($expectedLocation, $location);
    }
}
