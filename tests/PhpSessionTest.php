<?php

use Psr7Middlewares\Middleware;

class PhpSessionTest extends Base
{
    public function sessionDataProvider()
    {
        return [
            [
                'session_1',
                'Iván',
            ],[
                'session_2',
                'Pablo',
            ],
        ];
    }

    /**
     * @dataProvider sessionDataProvider
     */
    public function testPhpSession($sessionName, $value)
    {
        $response = $this->execute(
            [
                Middleware::PhpSession()->name($sessionName),
                function ($request, $response, $next) use ($value) {
                    $response->getBody()->write(session_name());
                    $_SESSION['name'] = $value;

                    return $next($request, $response);
                }
            ]
        );

        $this->assertEquals($sessionName, (string) $response->getBody());
        $this->assertEquals($value, $_SESSION['name']);
    }
}
