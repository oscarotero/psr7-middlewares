<?php
use Psr7Middlewares\Middleware;

class ExceptionHandlerTest extends Base
{
    public function testException()
    {
        $exception = new \Exception("Error Processing Request");
        $response = $this->execute(
            [
                Middleware::ExceptionHandler(),
                function ($request, $response, $next) use ($exception) {
                    throw $exception;
                },
            ]
        );

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals((string) $exception, (string) $response->getBody());
    }
}
