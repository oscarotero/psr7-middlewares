<?php

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;

class MethodOverrideTest extends Base
{
    public function methodOverrideProvider()
    {
        return [
            ['GET', 'HEAD', 200],
            ['POST', 'HEAD', 405],
            ['GET', 'POST', 405],
            ['GET', 'GET', 200],
        ];
    }

    /**
     * @dataProvider methodOverrideProvider
     */
    public function testLanguages($original, $overrided, $status)
    {
        $response = $this->dispatch(
            [
                Middleware::MethodOverride(),
            ],
            $this->request('', ['X-Http-Method-Override' => $overrided])->withMethod($original),
            $this->response()
        );

        $this->assertEquals($status, $response->getStatusCode());
    }

    public function getRequestProvider()
    {
        return [
            [
                $this->request('hello'),
                'GET', 200,
            ],
            [
                $this->request('hello')->withQueryParams(['method' => 'head']),
                'HEAD', 200,
            ],
            [
                $this->request('hello')->withQueryParams(['method' => 'PUT']),
                '', 405,
            ],
            [
                $this->request('hello')->withMethod('POST')->withQueryParams(['method' => 'PUT']),
                'POST', 200,
            ],
            [
                $this->request('hello')->withMethod('POST')->withParsedBody(['method' => 'PUT']),
                'PUT', 200,
            ],
            [
                $this->request('hello')->withMethod('POST')->withParsedBody(['method' => 'GET']),
                '', 405,
            ],
        ];
    }

    /**
     * @dataProvider getRequestProvider
     */
    public function testParams(ServerRequestInterface $request, $body, $status)
    {
        $response = $this->dispatch(
            [
                Middleware::MethodOverride()
                    ->parameter('method'),

                function ($request, $response, $next) {
                    $response->getBody()->write($request->getMethod());

                    return $next($request, $response);
                },
            ],
            $request,
            $this->response()
        );

        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($body, (string) $response->getBody());
    }
}
