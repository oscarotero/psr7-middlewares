<?php

use Psr7Middlewares\Middleware;

class WhoopsTest extends Base
{
    public function testWhoops()
    {
        $response = $this->execute(
            [
                Middleware::Whoops(),
                function ($request, $response, $next) {
                    throw new \Exception('Error Processing Request');
                },
            ]
        );

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertNotFalse(strpos($response->getBody(), 'Error Processing Request'));
    }
}
