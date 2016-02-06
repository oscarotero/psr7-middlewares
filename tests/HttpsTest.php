<?php

use Psr7Middlewares\Middleware;

class HttpsTest extends Base
{
    public function HttpsProvider()
    {
        return [
            ['http://localhost', true, 301, 'https://localhost', ''],
            ['https://localhost', false, 200, '', 'max-age=31536000'],
            ['https://localhost', true, 200, '', 'max-age=31536000;includeSubDomains'],
        ];
    }

    /**
     * @dataProvider HttpsProvider
     */
    public function testHttps($url, $includeSubdomains, $status, $location, $hsts)
    {
        $response = $this->execute(
            [
                Middleware::Https()->includeSubdomains($includeSubdomains),
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
        $this->assertEquals($expectedLocation,$location);
    }
}
