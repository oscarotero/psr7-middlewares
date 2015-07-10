<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\RelayBuilder;

class ErrorResponseHandlerTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $relayBuilder = new RelayBuilder();
        $dispatcher = $relayBuilder->newInstance([
            Middleware::ErrorResponseHandler(function ($request, $response) {
                $response->getBody()->write('Page not found');
            }),
            function ($request, $response, $next) {
                return $response->withStatus(404);
            },
        ]);

        $response = $dispatcher(new ServerRequest(), new Response());

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Page not found', (string) $response->getBody());
    }
}
