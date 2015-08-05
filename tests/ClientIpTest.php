<?php
use Psr7Middlewares\Middleware;

class ClientIpTest extends Base
{
    public function ipsProvider()
    {
        return [
            [
                [
                    'Client-Ip' => 'unknow,123.456.789.10,123.234.123.10',
                    'X-Forwarded' => '123.234.123.10',
                ],
                ['123.234.123.10'],
                '123.234.123.10',
            ],[
                [
                    'Client-Ip' => 'unknow,123.456.789.10,123.234.123.10',
                    'X-Forwarded' => '123.234.123.11',
                ],
                ['123.234.123.10', '123.234.123.11'],
                '123.234.123.10'
            ]
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
                        'CLIENT_IPS' => $request->getAttribute('CLIENT_IPS'),
                        'CLIENT_IP' => $request->getAttribute('CLIENT_IP'),
                    ]));

                    return $response;
                },
            ],
            '',
            $headers
        );

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals($body['CLIENT_IPS'], $CLIENT_IPS);
        $this->assertEquals($body['CLIENT_IP'], $CLIENT_IP);
    }
}
