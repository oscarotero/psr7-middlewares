<?php
use Psr7Middlewares\Middleware;

class FastRouteTest extends Base
{
    public function testFastRoute()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', function ($request, $response) {
                $response->getBody()->write(json_encode([
                    'name' => $request->getAttribute('name'),
                    'id' => $request->getAttribute('id'),
                ]));

                return $response;
            });
        });

        $response = $this->execute(
            [
                Middleware::FastRoute($dispatcher),
            ],
            'http://domain.com/user/oscarotero/35'
        );

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals($body['name'], 'oscarotero');
        $this->assertEquals($body['id'], '35');
    }
}
