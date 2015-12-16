<?php

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\AuraRouter;
use Aura\Router\RouterContainer;

class AuraRouterTest extends Base
{
    protected function getRouter()
    {
        //Create router
        $router = new RouterContainer();
        $map = $router->getMap();

        $map->get('index', '/user/{name}/{id}', function ($request, $response) {
            $this->assertEquals('oscarotero', $request->getAttribute('name'));
            $this->assertEquals('35', $request->getAttribute('id'));

            $this->assertInstanceOf('Aura\\Router\\Route', AuraRouter::getRoute($request));

            $response->getBody()->write('Ok');

            return $response;
        });

        return $router;
    }

    public function testAuraRouter()
    {
        //Test
        $response = $this->execute(
            [
                Middleware::AuraRouter()
                    ->router($this->getRouter()),
            ],
            'http://domain.com/user/oscarotero/35'
        );

        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testAuraRouterContainer()
    {
        //Test
        $response = $this->execute(
            [
                Middleware::AuraRouter($this->getRouter()),
            ],
            'http://domain.com/user/oscarotero/35'
        );

        $this->assertEquals('Ok', (string) $response->getBody());
    }
}
