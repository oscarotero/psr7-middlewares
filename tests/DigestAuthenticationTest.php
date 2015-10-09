<?php

use Psr7Middlewares\Middleware;

class DigestAuthenticationTest extends Base
{
    public function testAuthentication()
    {
        $response = $this->execute(
            [
                Middleware::DigestAuthentication([])->realm('My realm')->nonce('xxx'),
            ]
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Digest realm="My realm",qop="auth",nonce="xxx",opaque="'.md5('My realm').'"', $response->getHeaderLine('WWW-Authenticate'));
    }
}
