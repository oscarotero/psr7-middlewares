<?php
use Psr7Middlewares\Middleware;
use Aura\Router\RouterContainer;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Relay\Relay;

class AuraRouterTest extends PHPUnit_Framework_TestCase
{
    public function testAuraRouter()
    {
        $router = new RouterContainer();
        $map = $router->getMap();

        $map->get('index', '/user/{name}/{id}', function ($request, $response) {
            $this->assertEquals('oscarotero', $request->getAttribute('name'));
            $this->assertEquals('35', $request->getAttribute('id'));

            $response->getBody()->write('Ok');

            return $response;
        });

        $dispatcher = new Relay([
            Middleware::AuraRouter($router),
        ]);

        $request = (new ServerRequest())
            ->withUri(new Uri('http://domain.com/user/oscarotero/35'));

        $response = $dispatcher($request, new Response());

        $this->assertEquals('Ok', (string) $response->getBody());
    }
}
