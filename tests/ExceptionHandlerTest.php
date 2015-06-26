<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\RelayBuilder;

class ExceptionHandlerTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $relayBuilder = new RelayBuilder();
        $dispatcher = $relayBuilder->newInstance([
            Middleware::ExceptionHandler(),
            function ($request, $response, $next) {
                throw new \Exception("Error Processing Request");
            },
        ]);

        $response = $dispatcher(new ServerRequest(), new Response());

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Error Processing Request', (string) $response->getBody());
    }
}
