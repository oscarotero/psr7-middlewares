<?php

use Psr7Middlewares\Middleware;

class ShutdownTest extends Base
{
    public function testShutdown()
    {
        $body = 'Service unavailable';

        $response = $this->execute([
            Middleware::Shutdown(function () use ($body) {
                return $body;
            }),
        ]);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame($body, (string) $response->getBody());
    }
}
