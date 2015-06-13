<?php
use Psr7Middlewares\ClientIp;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Relay\Relay;

class ClientIpTest extends PHPUnit_Framework_TestCase
{
    public function testIps()
    {
        $tested = false;

        $dispatcher = new Relay([
            new ClientIp(),
            function ($request, $response, $next) use (&$tested) {
                $this->assertEquals('123.234.123.10', $request->getAttribute('CLIENT_IP'));
                $this->assertEquals(['123.234.123.10'], $request->getAttribute('CLIENT_IPS'));
                $tested = true;
            }
        ]);

        $request = (new ServerRequest())
            ->withHeader('Client-Ip', 'unknow,123.456.789.10,123.234.123.10')
            ->withHeader('X-Forwarded', '123.234.123.10');

        $response = $dispatcher($request, new Response());

        $this->assertTrue($tested);
    }
}
