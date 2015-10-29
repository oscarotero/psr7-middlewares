<?php

use Psr7Middlewares\Middleware;

class WhenTest extends Base
{
    public function whenDataProvider()
    {
        return [
            [false, 'Foo'],
            [true, 'Bar,Foo'],
            [function () {return false;}, 'Foo'],
            [function () {return true;}, 'Bar,Foo'],
        ];
    }

    /**
     * @dataProvider whenDataProvider
     */
    public function testWhenFalse($condition, $result)
    {
        $response = $this->execute(
            [
                Middleware::When($condition, function ($request, $response, $next) {
                    return $next($request, $response->withHeader('X-Foo', 'Bar'));
                }),
                function ($request, $response, $next) {
                    return $next($request, $response->withAddedHeader('X-Foo', 'Foo'));
                },
            ]
        );

        $this->assertSame($result, $response->getHeaderLine('X-Foo'));
    }
}
