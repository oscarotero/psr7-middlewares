<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\Relay;

class ExceptionHandlerTest extends PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $dispatcher = new Relay([
            Middleware::ExceptionHandler(),
            function ($request, $response, $next) {
                throw new \Exception("Error Processing Request");
            },
        ]);

        $response = $dispatcher(new ServerRequest(), new Response());

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Error Processing Request', (string) $response->getBody());
    }

    public function testCustomHandler()
    {
        $dispatcher = new Relay([
            Middleware::ExceptionHandler(function ($exception, $request, $response) {
                $response = (new Response())->withStatus(499);
                $response->getBody()->write('Oops!');

                return $response;
            }),
            function ($request, $response, $next) {
                throw new \Exception("Error Processing Request");
            },
        ]);

        $response = $dispatcher(new ServerRequest(), new Response());

        $this->assertEquals(499, $response->getStatusCode());
        $this->assertEquals('Oops!', (string) $response->getBody());
    }
}
