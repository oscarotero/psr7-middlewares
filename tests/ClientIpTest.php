<?php
use Psr7Middlewares\ClientIp;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\Relay;

class ClientIpTest extends PHPUnit_Framework_TestCase
{
    protected function makeTest(array $headers, array $client_ips, $client_ip)
    {
        $dispatcher = new Relay([
            new ClientIp(),
            function ($request, $response, $next) use ($client_ips, $client_ip) {
                $this->assertEquals($client_ips, $request->getAttribute('CLIENT_IPS'));
                $this->assertEquals($client_ip, $request->getAttribute('CLIENT_IP'));

                $response->getBody()->write('Ok');

                return $response;
            }
        ]);

        $request = (new ServerRequest());

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $response = $dispatcher($request, new Response());

        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testIps()
    {
        $this->makeTest([
                'Client-Ip' => 'unknow,123.456.789.10,123.234.123.10',
                'X-Forwarded' => '123.234.123.10'
            ],
            ['123.234.123.10'],
            '123.234.123.10'
        );

        $this->makeTest([
                'Client-Ip' => 'unknow,123.456.789.10,123.234.123.10',
                'X-Forwarded' => '123.234.123.11'
            ],
            ['123.234.123.10', '123.234.123.11'],
            '123.234.123.10'
        );
    }
}
