<?php
use Psr7Middlewares\Middleware;

class ErrorResponseHandlerTest extends Base
{
    public function testError()
    {
        $response = $this->execute(
            [
                Middleware::ErrorResponseHandler(function ($request, $response) {
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
}
