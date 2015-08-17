<?php
use Psr7Middlewares\Middleware;

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
        $exception = new \Exception("Error Processing Request");

        $response = $this->execute(
            [
                Middleware::ErrorHandler()
                    ->handler(function ($request, $response) {
                        $exception = $request->getAttribute('EXCEPTION');

                        $response->getBody()->write((string) $exception);
                    })
                    ->catchExceptions(),
                function ($request, $response, $next) use ($exception) {
                    throw $exception;
                },
            ]
        );

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals((string) $exception, (string) $response->getBody());
    }
}
