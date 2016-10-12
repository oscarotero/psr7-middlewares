<?php

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\ErrorHandler;

class ErrorHandlerTest extends Base
{
    public function testError()
    {
        $response = $this->execute(
            [
                Middleware::ErrorHandler(function ($request, $response) {
                    $response->getBody()->write('Page not found');
                }),
                function ($request, $response, $next) {
                    return $response->withStatus(404);
                },
            ]
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Page not found', (string) $response->getBody());
    }

    public function testException()
    {
        $exception = new \Exception('Error Processing Request');

        $response = $this->execute(
            [
                Middleware::ErrorHandler(function ($request, $response) {
                    $exception = ErrorHandler::getException($request);

                    $response->getBody()->write((string) $exception);
                })
                    ->catchExceptions(),
                function ($request, $response, $next) use ($exception) {
                    $response->getBody()->write('not showed text');
                    throw $exception;
                },
            ]
        );

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals((string) $exception, (string) $response->getBody());
    }
}
