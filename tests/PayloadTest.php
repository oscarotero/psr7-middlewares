<?php

use Psr7Middlewares\Middleware;

class PayloadTest extends Base
{
    public function payloadProvider()
    {
        return [
            ['application/json', '{"bar":"foo"}', ['bar' => 'foo'], true],
            ['application/json', '{"bar":"foo"}', (object) ['bar' => 'foo'], false],
            ['application/x-www-form-urlencoded', 'bar=foo', ['bar' => 'foo'], false],
            ['application/x-www-form-urlencoded', '', [], false],
        ];
    }

    /**
     * @dataProvider payloadProvider
     */
    public function testTrailingSlash($header, $body, $result, $associative)
    {
        $request = $this->request('', ['Content-Type' => $header])
            ->withMethod('POST')
            ->withBody($this->stream($body));

        $response = $this->dispatch([
            Middleware::Payload()->associative($associative),

            function ($request, $response, $next) use ($result) {
                $this->assertEquals($result, $request->getParsedBody());
            },
        ], $request, $this->response());
    }
}
