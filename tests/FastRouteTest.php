<?php
use Psr7Middlewares\FastRoute;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Relay\Relay;

class FastRouteTest extends PHPUnit_Framework_TestCase
{
    public function testFastRoute()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', function ($request, $response) {
                $this->assertEquals('oscarotero', $request->getAttribute('name'));
                $this->assertEquals('35', $request->getAttribute('id'));

                $response->getBody()->write('Ok');

                return $response;
            });
        });

        $dispatcher = new Relay([
            new FastRoute($dispatcher)
        ]);

        $request = (new ServerRequest())
            ->withUri(new Uri('http://domain.com/user/oscarotero/35'));

        $response = $dispatcher($request, new Response());

        $this->assertEquals('Ok', (string) $response->getBody());
    }
}
