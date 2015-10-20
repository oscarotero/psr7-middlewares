<?php

use Psr7Middlewares\Middleware;

class ResponseTimeTest extends Base
{
    public function testResponseTime()
    {
        $response = $this->execute([
            Middleware::responseTime(),
        ]);

        $this->assertNotEmpty($response->getHeaderLine('X-Response-Time'));
    }
}
