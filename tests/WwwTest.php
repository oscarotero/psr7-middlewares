<?php

use Psr7Middlewares\Middleware;

class WwwTest extends Base
{
    public function addWwwProvider()
    {
        return [
            ['http://localhost', 'http://localhost'],
            ['http://localhost.com', 'http://www.localhost.com'],
            ['http://example.com', 'http://www.example.com'],
            ['http://example.co.uk', 'http://www.example.co.uk'],
            ['http://www.example.com', 'http://www.example.com'],
            ['http://ww1.example.com', 'http://ww1.example.com'],
            ['', ''],
        ];
    }

    /**
     * @dataProvider addWwwProvider
     */
    public function testAddWww($url, $result)
    {
        $response = $this->execute(
            [
                Middleware::Www(true),

                function ($request, $response, $next) {
                    $response->getBody()->write((string) $request->getUri());

                    return $response;
                },
            ],
            $url
        );

        $this->assertEquals($result, (string) $response->getBody());
    }

    public function removeWwwProvider()
    {
        return [
            ['http://localhost', 'http://localhost'],
            ['http://www.localhost.com', 'http://localhost.com'],
            ['http://www.example.com', 'http://example.com'],
            ['http://www.example.co.uk', 'http://example.co.uk'],
            ['http://www.example.com', 'http://example.com'],
            ['http://ww1.example.com', 'http://ww1.example.com'],
            ['', ''],
        ];
    }

    /**
     * @dataProvider removeWwwProvider
     */
    public function testRemoveWww($url, $result)
    {
        $response = $this->execute(
            [
                Middleware::Www(false),

                function ($request, $response, $next) {
                    $response->getBody()->write((string) $request->getUri());

                    return $response;
                },
            ],
            $url
        );

        $this->assertEquals($result, (string) $response->getBody());
    }

    public function testWwwRedirect()
    {
        $response = $this->execute(
            [
                Middleware::Www(false)->redirect(),
            ],
            'http://www.example.com'
        );

        $this->assertEquals(302, (string) $response->getStatusCode());
        $this->assertEquals('http://example.com', $response->getHeaderLine('location'));
    }
}
