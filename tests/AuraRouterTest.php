<?php
use Psr7Middlewares\Middleware;
use Aura\Router\RouterContainer;

class AuraRouterTest extends Base
{
    public function testAuraRouter()
    {
        //Create router
        $router = new RouterContainer();
        $map = $router->getMap();

        $map->get('index', '/user/{name}/{id}', function ($request, $response) {
            $this->assertEquals('oscarotero', $request->getAttribute('name'));
            $this->assertEquals('35', $request->getAttribute('id'));

            $response->getBody()->write('Ok');

            return $response;
        });

        //Test
        $response = $this->execute(
            [
                Middleware::AuraRouter($router),
            ],
            'http://domain.com/user/oscarotero/35'
        );

        $this->assertEquals('Ok', (string) $response->getBody());
    }
}
