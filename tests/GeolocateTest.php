<?php

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\Geolocate;

class GeolocateTest extends Base
{
    public function testLocation()
    {
        $request = $this->request('', [
            'Client-Ip' => '123.9.34.23',
        ]);

        //Test
        $response = $this->dispatch(
            [
                Middleware::clientIp(),
                Middleware::geolocate(),
                function ($request, $response, $next) {
                    $locate = Geolocate::getLocation($request);

                    $response->getBody()->write($locate->first()->getCountry());

                    return $next($request, $response);
                },
            ],
            $request, $this->response()
        );

        $this->assertEquals('China', (string) $response->getBody());
    }
}
