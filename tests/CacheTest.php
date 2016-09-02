<?php

use Psr7Middlewares\Middleware;
use MatthiasMullie\Scrapbook\Adapters\MemoryStore;
use MatthiasMullie\Scrapbook\Psr6\Pool;

class CacheTest extends Base
{
    public function testExpiration()
    {
        $used = 0;

        $middlewares = [
            Middleware::Cache(new Pool(new MemoryStore()))->cacheControl('max-age=3600'),

            function ($request, $response, $next) use (&$used) {
                ++$used;
                $response->getBody()->write('hello');

                return $next($request, $response->withHeader('Cache-Control', 'max-age=6000'));
            },
        ];

        //Test
        $response1 = $this->dispatch($middlewares, $this->request(), $this->response());
        $response2 = $this->dispatch($middlewares, $this->request()->withHeader('If-Modified-Since', (new \Datetime('now'))->format('D, d M Y H:i:s')), $this->response());

        $this->assertEquals('hello', (string) $response1->getBody());
        $this->assertEquals('', (string) $response2->getBody());
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(304, $response2->getStatusCode());

        $this->assertSame(1, $used);
    }
}
