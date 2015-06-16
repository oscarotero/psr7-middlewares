<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\Relay;

class FirewallTest extends PHPUnit_Framework_TestCase
{
    protected function makeTest(array $headers, $trusted, $untrusted, $status)
    {
        $dispatcher = new Relay([
            Middleware::ClientIp(),
            Middleware::Firewall($trusted, $untrusted)
        ]);

        $request = (new ServerRequest());

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $response = $dispatcher($request, new Response());

        $this->assertEquals($status, $response->getStatusCode());
    }

    public function testIps()
    {
        $this->makeTest([
                'Client-Ip' => 'unknow,123.456.789.10,123.234.123.10',
                'X-Forwarded' => '123.234.123.10',
            ],
            [],
            [],
            403
        );

        $this->makeTest([
                'Client-Ip' => 'unknow,123.456.789.10,123.234.123.10',
                'X-Forwarded' => '123.234.123.10',
            ],
            ['123.234.123.10'],
            [],
            200
        );

        $this->makeTest([
                'Client-Ip' => 'unknow,123.456.789.10,123.234.123.11',
                'X-Forwarded' => '123.234.123.10',
            ],
            ['123.234.123.11'],
            ['123.234.123.10'],
            403
        );

        $this->makeTest([
                'Client-Ip' => '123.0.0.10,123.0.0.11',
                'X-Forwarded' => '123.0.0.12',
            ],
            '123.0.0.*',
            [],
            200
        );

        $this->makeTest([
                'Client-Ip' => '123.0.0.10,123.0.0.11',
                'X-Forwarded' => '123.0.0.12',
            ],
            ['123.0.0.*'],
            ['123.0.0.12'],
            403
        );
    }
}
