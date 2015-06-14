<?php
use Psr7Middlewares\Middleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Relay\Relay;

class AcceptTypeTest extends PHPUnit_Framework_TestCase
{
    protected function makeTest($path, $header, $availables, array $accept_type, $preferred_format, $preferred_type)
    {
        $dispatcher = new Relay([
            Middleware::AcceptType($availables),
            function ($request, $response, $next) use ($accept_type, $preferred_format, $preferred_type) {
                $this->assertEquals($accept_type, $request->getAttribute('ACCEPT_TYPE'));
                $this->assertEquals($preferred_format, $request->getAttribute('PREFERRED_FORMAT'));
                $this->assertEquals($preferred_type, $request->getAttribute('PREFERRED_TYPE'));

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
            ['html'],
            ['application/xml' => 1, 'application/xhtml+xml' => 1, 'text/html' => 0.9, 'text/plain' => 0.8, 'image/png' => 1, '*/*' => 0.5],
            'html',
            'application/xhtml+xml'
        );

        $this->makeTest(
            '/test.json',
            '',
            null,
            [],
            'json',
            'application/json'
        );

        $this->makeTest(
            '/test.json',
            '',
            ['gif', 'html'],
            [],
            'html',
            'text/html'
        );

        $this->makeTest(
            '/',
            '',
            null,
            [],
            'html',
            'text/html'
        );

        $this->makeTest(
            '/',
            'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
            null,
            ['application/xml' => 1, 'application/xhtml+xml' => 1, 'text/html' => 0.9, 'text/plain' => 0.8, 'image/png' => 1, '*/*' => 0.5],
            'xml',
            'application/xml'
        );
    }
}
