<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Relay\RelayBuilder;

class TrailingSlashTest extends PHPUnit_Framework_TestCase
{
    protected function makeTest($path, $result)
    {
        $relayBuilder = new RelayBuilder();
        $dispatcher = $relayBuilder->newInstance([
            Middleware::trailingSlash(),
            function ($request, $response, $next) use ($result) {
                $this->assertEquals($result, $request->getUri()->getPath());

                $response->getBody()->write('Ok');

                return $response;
            },
        ]);

        $request = (new ServerRequest())
            ->withUri(new Uri($path));

        $response = $dispatcher($request, new Response());

        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testTrailingSlash()
    {
        $this->makeTest('/foo/bar', '/foo/bar');
        $this->makeTest('/foo/bar/', '/foo/bar');
        $this->makeTest('/', '/');
        $this->makeTest('', '');
    }
}
