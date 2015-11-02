<?php

use Psr7Middlewares\Middleware;
use Neomerx\Cors\Strategies\Settings;
use Neomerx\Cors\Contracts\Constants\CorsResponseHeaders;

class CorsTest extends Base
{
    private $settings;

    public function setUp()
    {
        $this->settings = (new Settings())
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
    }

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
        $response = $this->execute(
            [
                Middleware::cors()->settings($this->settings),
            ],
            $url
        );

        $this->assertEquals($statusCode, $response->getStatusCode());
    }

    /**
     * @dataProvider corsProvider
     */
    public function testCorsContainer($url, $statusCode)
    {
        $container = new ServiceContainer();
        $container->set('settings', $this->settings);

        $response = $this->execute(
            [
                Middleware::cors()->from($container, 'settings'),
            ],
            $url
        );

        $this->assertEquals($statusCode, $response->getStatusCode());
    }
}
