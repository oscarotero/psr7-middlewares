<?php

use Psr7Middlewares\Middleware;

class FirewallTest extends Base
{
    public function ipsProvider()
    {
        return [
            [
                [
                    'Client-Ip' => 'unknow,123.456.789.10,123.234.123.10',
                    'X-Forwarded' => '123.234.123.10',
                ],
                [],
                [],
                403,
            ], [
                [
                    'Client-Ip' => 'unknow,123.456.789.10,123.234.123.10',
                    'X-Forwarded' => '123.234.123.10',
                ],
                ['123.234.123.10'],
                [],
                200,
            ], [
                [
                    'Client-Ip' => 'unknow,123.456.789.10,123.234.123.11',
                    'X-Forwarded' => '123.234.123.10',
                ],
                ['123.234.123.11'],
                ['123.234.123.10'],
                403,
            ], [
                [
                    'Client-Ip' => '123.0.0.10,123.0.0.11',
                    'X-Forwarded' => '123.0.0.12',
                ],
                ['123.0.0.*'],
                [],
                200,
            ], [
                [
                    'Client-Ip' => '123.0.0.10,123.0.0.11',
                    'X-Forwarded' => '123.0.0.12',
                ],
                ['123.0.0.*'],
                ['123.0.0.12'],
                403,
            ],
        ];
    }

    /**
     * @dataProvider ipsProvider
     */
    public function testFirewall(array $headers, array $trusted, array $untrusted, $status)
    {
        $response = $this->execute(
            [
                Middleware::ClientIp()->headers(),
                Middleware::Firewall()
                    ->trusted($trusted)
                    ->untrusted($untrusted),
            ],
            '',
            $headers
        );

        $this->assertEquals($status, $response->getStatusCode());
    }
}
