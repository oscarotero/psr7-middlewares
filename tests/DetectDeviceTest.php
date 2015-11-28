<?php

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\DetectDevice;

class DetectDeviceTest extends Base
{
    public function testDevice()
    {
        $request = $this->request('', [], [
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:42.0) Gecko/20100101 Firefox/42.0',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'gl-GL,gl;q=0.8,es;q=0.6,en-US;q=0.4,en;q=0.2',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
            'HTTP_DNT' => '1',
            'HTTP_CACHE_CONTROL' => 'max-age=0',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
        ]);

        //Test
        $response = $this->dispatch(
            [
                Middleware::DetectDevice(),
                function ($request, $response, $next) {
                    $device = DetectDevice::getDevice($request);
                    $body = $response->getBody();

                    $body->write($device->isMobile() ? '1' : '0');
                    $body->write($device->isTablet() ? '1' : '0');

                    return $next($request, $response);
                },
            ],
            $request, $this->response()
        );

        $this->assertEquals('00', (string) $response->getBody());
    }
}
