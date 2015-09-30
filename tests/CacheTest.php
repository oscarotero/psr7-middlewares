<?php
use Psr7Middlewares\Middleware;
use MatthiasMullie\Scrapbook\Adapters\MemoryStore;
use MatthiasMullie\Scrapbook\Psr6\Pool;

class CacheTest extends Base
{
    public function testCache()
    {
        $cache = new Pool(new MemoryStore());
        $used = 0;

        $middlewares = [
            Middleware::Cache($cache),

            function ($request, $response, $next) use (&$used) {
                $response->getBody()->write(uniqid('test', true));

                ++$used;

                return $next($request, $response);
            },
        ];

        //Test
        $response1 = $this->dispatch($middlewares, $this->request(), $this->response());
        $response2 = $this->dispatch($middlewares, $this->request(), $this->response());
        $response3 = $this->dispatch($middlewares, $this->request()->withMethod('POST'), $this->response());
        $response4 = $this->dispatch($middlewares, $this->request()->withMethod('POST'), $this->response());
        $response5 = $this->dispatch($middlewares, $this->request()->withMethod('GET'), $this->response());

        $this->assertEquals((string) $response1->getBody(), (string) $response2->getBody());
        $this->assertNotEquals((string) $response2->getBody(), (string) $response3->getBody());
        $this->assertNotEquals((string) $response3->getBody(), (string) $response4->getBody());
        $this->assertNotEquals((string) $response4->getBody(), (string) $response5->getBody());
        $this->assertEquals((string) $response1->getBody(), (string) $response5->getBody());

        $this->assertSame(3, $used);
    }

    public function testExpiration()
    {
        $cache = new Pool(new MemoryStore());
        $used = 0;

        $middlewares = [
            Middleware::Cache($cache),

            function ($request, $response, $next) use (&$used) {
                $response->getBody()->write(uniqid('test', true));

                ++$used;

                return $next($request, $response->withHeader('Expires', (new \Datetime('-1 day'))->format('D, d M Y H:i:s')));
            },
        ];

        //Test
        $response1 = $this->dispatch($middlewares, $this->request(), $this->response());
        $response2 = $this->dispatch($middlewares, $this->request(), $this->response());

        $this->assertNotEquals((string) $response1->getBody(), (string) $response2->getBody());

        $this->assertSame(2, $used);
    }
}
