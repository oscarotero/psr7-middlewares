<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Relay\Relay;

class BasePathTest extends PHPUnit_Framework_TestCase
{
    protected function makeTest($url, $basepath, $result)
    {
        $dispatcher = new Relay([
            Middleware::BasePath($basepath),
            function ($request, $response, $next) use ($result) {
                $this->assertEquals($result, (string) $request->getUri());

                $response->getBody()->write('Ok');

                return $response;
            },
        ]);

        $request = (new ServerRequest())
            ->withUri(new Uri($url));

        $response = $dispatcher($request, new Response());

        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testBasePath()
    {
        $this->makeTest(
            'http://localhost/project-name/public',
            '/project-name/public',
            'http://localhost'
        );

        $this->makeTest(
            'http://localhost/project-name/public',
            '/other/path',
            'http://localhost/project-name/public'
        );

        $this->makeTest(
            '/project-name/public',
            '/project-name',
            '/public'
        );
    }
}
