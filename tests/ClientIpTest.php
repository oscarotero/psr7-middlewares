<?php

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\ClientIp;

class ClientIpTest extends Base
{
    public function ipsProvider()
    {
        return [
            [
                [
                    'Client-Ip'   => 'unknow,123.456.789.10,123.234.123.10',
                    'X-Forwarded' => '123.234.123.10',
                ],
                ['123.234.123.10'],
                '123.234.123.10',
            ],
            [
                [
                    'Client-Ip'   => 'unknow,123.456.789.10,123.234.123.10',
                    'X-Forwarded' => '123.234.123.11',
                ],
                ['123.234.123.10', '123.234.123.11'],
                '123.234.123.10',
            ],
        ];
    }

    public function srvProvider()
    {
        return [
            [
                [
                    'REMOTE_ADDR' => '123.234.123.10'
                ],
                ['123.234.123.10'],
                '123.234.123.10',
            ],
            [
                [
                    'REMOTE_ADDR'          => '123.234.123.10',
                    'HTTP_X_FORWARDED_FOR' => '222.234.123.10'
                ],
                ['123.234.123.10', '222.234.123.10'],
                '123.234.123.10',
            ],
            [
                [
                    'HTTP_X_FORWARDED_FOR' => '222.234.123.10',
                    'REMOTE_ADDR'          => '123.234.123.10'
                ],
                ['123.234.123.10', '222.234.123.10'],
                '123.234.123.10',
            ],
            [
                [
                    'HTTP_X_FORWARDED_FOR' => '222.234.123.10'
                ],
                ['222.234.123.10'],
                '222.234.123.10',
            ],
        ];
    }

    public function srvReversedProvider()
    {
        return [
            [
                [
                    'REMOTE_ADDR' => '123.234.123.10'
                ],
                ['123.234.123.10'],
                '123.234.123.10',
            ],
            [
                [
                    'REMOTE_ADDR'          => '123.234.123.10',
                    'HTTP_X_FORWARDED_FOR' => '222.234.123.10'
                ],
                ['222.234.123.10', '123.234.123.10'],
                '222.234.123.10',
            ],
            [
                [
                    'HTTP_X_FORWARDED_FOR' => '222.234.123.10',
                    'REMOTE_ADDR'          => '123.234.123.10'
                ],
                ['222.234.123.10', '123.234.123.10'],
                '222.234.123.10',
            ],
            [
                [
                    'HTTP_X_FORWARDED_FOR' => '222.234.123.10'
                ],
                ['222.234.123.10'],
                '222.234.123.10',
            ],
        ];
    }

    /**
     * @dataProvider ipsProvider
     */
    public function testIps(array $headers, array $CLIENT_IPS, $CLIENT_IP)
    {
        $response = $this->execute(
            [
                Middleware::ClientIp(),
                function ($request, $response, $next) {
                    $response->getBody()->write(json_encode([
                        'CLIENT_IPS' => ClientIp::getIps($request),
                        'CLIENT_IP'  => ClientIp::getIp($request),
                    ]));

                    return $response;
                },
            ],
            '',
            $headers
        );

        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals($body['CLIENT_IPS'], $CLIENT_IPS);
        $this->assertEquals($body['CLIENT_IP'], $CLIENT_IP);
    }

    /**
     * @dataProvider srvProvider
     */
    public function testServerOrderCheck(array $srvOptions, array $CLIENT_IPS, $CLIENT_IP)
    {
        $response = $this->executeWithServer(
            [
                //Emulates default order
                Middleware::ClientIp(),
                function ($request, $response, $next) {
                    $response->getBody()->write(json_encode([
                        'CLIENT_IPS' => ClientIp::getIps($request),
                        'CLIENT_IP'  => ClientIp::getIp($request),
                    ]));

                    return $response;
                },
            ],
            '',
            $srvOptions
        );

        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals($body['CLIENT_IPS'], $CLIENT_IPS);
        $this->assertEquals($body['CLIENT_IP'], $CLIENT_IP);
    }

    /**
     * @dataProvider srvReversedProvider
     */
    public function testServerOrderCheckReversed(array $srvOptions, array $CLIENT_IPS, $CLIENT_IP)
    {
        $response = $this->executeWithServer(
            [
                //Emulates reversed order
                Middleware::ClientIp()->serverOptions(['HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR']),
                function ($request, $response, $next) {
                    $response->getBody()->write(json_encode([
                        'CLIENT_IPS' => ClientIp::getIps($request),
                        'CLIENT_IP'  => ClientIp::getIp($request),
                    ]));

                    return $response;
                },
            ],
            '',
            $srvOptions
        );

        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals($body['CLIENT_IPS'], $CLIENT_IPS);
        $this->assertEquals($body['CLIENT_IP'], $CLIENT_IP);
    }

    /**
     * @param callable[] $middlewares
     * @param string     $url
     * @param array      $serverVars
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function executeWithServer(array $middlewares, $url = '', array $serverVars = [])
    {
        $request = $this->request($url, [], $serverVars);
        $response = $this->response();

        return $this->dispatch($middlewares, $request, $response);
    }
}
