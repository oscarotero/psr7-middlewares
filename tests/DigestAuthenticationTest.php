<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\Relay;

class DigestAuthenticationTest extends PHPUnit_Framework_TestCase
{
    public function testIps()
    {
        $dispatcher = new Relay([
            Middleware::DigestAuthentication([], 'Login', 'xxx'),
        ]);

        $response = $dispatcher(new ServerRequest(), new Response());

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Digest realm="Login",qop="auth",nonce="xxx",opaque="'.md5('Login').'"', $response->getHeaderLine('WWW-Authenticate'));
    }
}
