<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Relay\Relay;

class FormatNegotiatorTest extends PHPUnit_Framework_TestCase
{
    protected function makeTest($path, $header, $format)
    {
        $dispatcher = new Relay([
            Middleware::FormatNegotiator(),
            function ($request, $response, $next) use ($format) {
                $this->assertEquals($format, $request->getAttribute('FORMAT'));

                $response->getBody()->write('Ok');

                return $response;
            },
        ]);

        $request = (new ServerRequest())
            ->withUri(new Uri($path))
            ->withHeader('Accept', $header);

        $response = $dispatcher($request, new Response());

        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testTypes()
    {
        $this->makeTest(
            '/',
            'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
            'xml'
        );

        $this->makeTest(
            '/test.json',
            '',
            'json'
        );

        $this->makeTest(
            '/test.json',
            '',
            'json'
        );

        $this->makeTest(
            '/',
            '',
            null
        );

        $this->makeTest(
            '/',
            'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
            'xml'
        );
    }
}
