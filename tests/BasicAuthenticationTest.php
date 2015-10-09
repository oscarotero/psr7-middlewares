<?php

use Psr7Middlewares\Middleware;

class BasicAuthenticationTest extends Base
{
    public function testAuthentication()
    {
        $response = $this->execute(
            [
                Middleware::BasicAuthentication([])->realm('My realm'),
            ]
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Basic realm="My realm"', $response->getHeaderLine('WWW-Authenticate'));
    }
}
