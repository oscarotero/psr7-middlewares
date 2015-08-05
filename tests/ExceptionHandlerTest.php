<?php
use Psr7Middlewares\Middleware;

class ExceptionHandlerTest extends Base
{
    public function testException()
    {
        $response = $this->execute(
            [
                Middleware::ExceptionHandler(function () {
                    return $this->stream();
                }),
                function ($request, $response, $next) {
                    throw new \Exception("Error Processing Request");
                },
            ]
        );

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Error Processing Request', (string) $response->getBody());
    }
}
