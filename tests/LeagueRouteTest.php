<?php

use Psr7Middlewares\Middleware;
use League\Route\RouteCollection;

class LeagueRouteTest extends Base
{
    public function testLeagueRoute()
    {
        $router = new RouteCollection();

        $router->get('/user/{name}/{id:[0-9]+}', function ($request, $response, $vars) {
            return json_encode([
                'name' => $vars['name'],
                'id' => $vars['id'],
            ]);
        });

        $response = $this->execute(
            [
                Middleware::LeagueRoute($router),
            ],
            'http://domain.com/user/oscarotero/35'
        );

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals($body['name'], 'oscarotero');
        $this->assertEquals($body['id'], '35');
    }
}
