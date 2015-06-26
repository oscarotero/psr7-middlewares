<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\RelayBuilder;

class BasicAuthenticationTest extends PHPUnit_Framework_TestCase
{
    public function testIps()
    {
        $relayBuilder = new RelayBuilder();
        $dispatcher = $relayBuilder->newInstance([
            Middleware::BasicAuthentication([], 'Login'),
        ]);

        $response = $dispatcher(new ServerRequest(), new Response());

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Basic realm="Login"', $response->getHeaderLine('WWW-Authenticate'));
    }
}
