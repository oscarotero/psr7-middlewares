<?php

use Psr7Middlewares\Middleware;

class AttributeMapperTest extends Base
{
    public function testUsernameMiddlewareRouting()
    {
        $response = $this->execute(
            [
                Middleware::BasicAuthentication(['username' => 'password'])->realm('My realm'),

                Middleware::attributeMapper([
                    Middleware\BasicAuthentication::KEY => 'auth:username',
                ]),

                function ($request, $response, $next) {
                    $response->getBody()->write($request->getAttribute('auth:username'));

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
