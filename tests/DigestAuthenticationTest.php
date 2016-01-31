<?php

use Psr7Middlewares\Middleware;

class DigestAuthenticationTest extends Base
{
    public function testAuthentication()
    {
        $response = $this->execute(
            [
                Middleware::DigestAuthentication([])->realm('My realm')->nonce('xxx'),
            ]
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Digest realm="My realm",qop="auth",nonce="xxx",opaque="'.md5('My realm').'"',
            $response->getHeaderLine('WWW-Authenticate'));
    }

    public function testSuccessAuthentication()
    {
        //Pre-shared
        $nonce = uniqid();
        $response = $this->execute(
            [
                Middleware::DigestAuthentication(['username' => 'password'])->nonce($nonce)->realm('My Realm'),
                function ($request, $response, $next) {
                    $response->getBody()->write(Middleware\DigestAuthentication::getUsername($request));

                    return $response;
                },
            ],
            '',
            [
                'Authorization' => $this->authHeader('username', 'password', 'My Realm', $nonce),
            ]
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('username', (string) $response->getBody());
    }

    /**
     * @see https://tools.ietf.org/html/rfc2069#page-10
     *
     * @param string $username
     * @param string $password
     * @param string $realm
     * @param string $nonce
     * @param string $method
     * @param string $uri
     *
     * @return string
     */
    private function authHeader($username, $password, $realm, $nonce, $method = 'GET', $uri = '/')
    {
        $nc = '00000001';
        $cnonce = uniqid();
        $qop = 'auth';
        $opaque = md5($realm);

        $A1 = md5("{$username}:{$realm}:{$password}");
        $A2 = md5("{$method}:{$uri}");

        $response = md5("{$A1}:{$nonce}:{$nc}:{$cnonce}:{$qop}:{$A2}");
        $chunks = compact(
            'uri',
            'username',
            'realm',
            'nonce',
            'response',
            'qop',
            'nc',
            'opaque',
            'cnonce'
        );

        $header = [];
        foreach ($chunks as $name => $value) {
            $header[] = "{$name}=\"{$value}\"";
        }

        return 'Digest '.implode(', ', $header);
    }
}
