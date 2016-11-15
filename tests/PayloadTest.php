<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr7Middlewares\Middleware;

class PayloadTest extends Base
{
    public function payloadProvider()
    {
        return [
            ['application/json', '{"bar":"foo"}', ['bar' => 'foo']],
            ['application/json', '', []],
            ['application/x-www-form-urlencoded', 'bar=foo', ['bar' => 'foo']],
            ['application/x-www-form-urlencoded', '', []],
            ['text/csv', "one,two\nthree,four", [['one', 'two'], ['three', 'four']]],
        ];
    }

    /**
     * @dataProvider payloadProvider
     */
    public function testPayload($header, $body, $result)
    {
        $request = $this->request('', ['Content-Type' => $header])
            ->withMethod('POST')
            ->withBody($this->stream($body));

        $response = $this->dispatch(
            [
                Middleware::Payload(),
                function ($request, $response, $next) use ($result) {
                    $this->assertEquals($result, $request->getParsedBody());
                },
            ],
            $request,
            $this->response()
        );
    }

    public function testError()
    {
        $request = $this->request('', ['Content-Type' => 'application/json'])
            ->withMethod('POST')
            ->withBody($this->stream('{invalid:"json"}'));

        $response = $this->dispatch(
            [
                Middleware::Payload(),
            ],
            $request,
            $this->response()
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testJsonObjectPayload()
    {
        $request = $this->request('', ['Content-Type' => 'application/json'])
            ->withMethod('POST')
            ->withBody($this->stream('{"foo":"bar","fiz":{"buz":true}}'));

        $this->dispatch(
            [
                new Middleware\Payload(['forceArray' => false]),
                function (ServerRequestInterface $request) {
                    $result = $request->getParsedBody();
                    $this->assertInstanceOf(\stdClass::class, $result);
                    $this->assertObjectHasAttribute('foo', $result);
                    $this->assertEquals('bar', $result->foo);
                    $this->assertObjectHasAttribute('fiz', $result);
                    $this->assertInstanceOf(\stdClass::class, $result->fiz);
                    $this->assertObjectHasAttribute('buz', $result->fiz);
                    $this->assertTrue($result->fiz->buz);
                }
            ],
            $request,
            $this->response()
        );
    }

    public function testParsingIsSkippedIfBodyAlreadyParsed()
    {
        $request = $this->request('', ['Content-Type' => 'application/json'])
            ->withMethod('POST')
            ->withBody($this->stream('{"foo":"bar"}'))
            ->withParsedBody(['other body']);

        $this->dispatch(
            [
                new Middleware\Payload(),
                function (ServerRequestInterface $request) {
                    $result = $request->getParsedBody();
                    $this->assertEquals(['other body'], $result);
                }
            ],
            $request,
            $this->response()
        );
    }
}
