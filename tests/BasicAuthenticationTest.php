<?php

use Psr7Middlewares\Middleware;

class BasicAuthenticationTest extends Base
{
    public function testAuthentication()
    {
        $response = $this->execute(
            [
                Middleware::BasicAuthentication([])->realm('My realm'),
            ]
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Basic realm="My realm"', $response->getHeaderLine('WWW-Authenticate'));
    }

    public function testSuccessAuthentication()
    {
        $response = $this->execute(
            [
                Middleware::BasicAuthentication(['username' => 'password'])->realm('My realm'),
                function ($request, $response, $next) {
                    $response->getBody()->write(Middleware\BasicAuthentication::getUsername($request));

                    return $response;
                },
            ],
            '',
            [
                'Authorization' => $this->authHeader('username', 'password'),
            ]
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('username', (string) $response->getBody());
    }

    private function authHeader($username, $password)
    {
        return 'Basic '.base64_encode("{$username}:{$password}");
    }
}
