<?php

use Psr7Middlewares\Middleware;
use Neomerx\Cors\Strategies\Settings;
use Neomerx\Cors\Contracts\Constants\CorsResponseHeaders;

class CorsTest extends Base
{
    public function corsProvider()
    {
        return [
            ['http://not-valid.com:321', 403],
            ['http://example.com:123', 200],
        ];
    }

    /**
     * @dataProvider corsProvider
     */
    public function testCors($url, $statusCode)
    {
        $settings = (new Settings())
            ->setServerOrigin([
                'scheme' => 'http',
                'host' => 'example.com',
                'port' => '123',
            ])
            ->setRequestAllowedOrigins([
                'http://good.example.com:321' => true,
                'http://evil.example.com:123' => null,
                CorsResponseHeaders::VALUE_ALLOW_ORIGIN_ALL => null,
                CorsResponseHeaders::VALUE_ALLOW_ORIGIN_NULL => null,
            ])
            ->setRequestAllowedMethods([
                'GET' => true,
                'PATCH' => null,
                'POST' => true,
                'PUT' => null,
                'DELETE' => true,
            ])
            ->setRequestAllowedHeaders([
                'content-type' => true,
                'some-disabled-header' => null,
                'x-enabled-custom-header' => true,
            ])
            ->setResponseExposedHeaders([
                'Content-Type' => true,
                'X-Custom-Header' => true,
                'X-Disabled-Header' => null,
            ])
            ->setRequestCredentialsSupported(false)
            ->setPreFlightCacheMaxAge(0)
            ->setForceAddAllowedMethodsToPreFlightResponse(true)
            ->setForceAddAllowedHeadersToPreFlightResponse(true)
            ->setCheckHost(true);

        $response = $this->execute(
            [
                Middleware::cors($settings),
            ],
            $url
        );

        $this->assertEquals($statusCode, $response->getStatusCode());
    }

    /**
     * @dataProvider corsProvider
     */
    public function testCors2($url, $statusCode)
    {
        $response = $this->execute(
            [
                Middleware::cors()
                    ->origin([
                        'scheme' => 'http',
                        'host' => 'example.com',
                        'port' => '123',
                    ])
                    ->allowedOrigins([
                        'http://good.example.com:321' => true,
                        'http://evil.example.com:123' => null,
                        '*' => null,
                        'null' => null,
                    ])
                    ->allowedMethods([
                        'GET' => true,
                        'PATCH' => null,
                        'POST' => true,
                        'PUT' => null,
                        'DELETE' => true,
                    ], true)
                    ->allowedHeaders([
                        'content-type' => true,
                        'some-disabled-header' => null,
                        'x-enabled-custom-header' => true,
                    ], true)
                    ->exposedHeaders([
                        'Content-Type' => true,
                        'X-Custom-Header' => true,
                        'X-Disabled-Header' => null,
                    ])
                    ->allowCredentials()
                    ->maxAge(0)
                    ->checkHost(true),
            ],
            $url
        );

        $this->assertEquals($statusCode, $response->getStatusCode());
    }
}
