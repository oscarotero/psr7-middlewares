<?php

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\EncodingNegotiator;

class EncodingNegotiatorTest extends Base
{
    public function encodingsProvider()
    {
        return [
            [
                'gzip,deflate',
                ['gzip'],
                'gzip',
            ],[
                'gzip,deflate',
                ['deflate', 'gzip'],
                'deflate',
            ],[
                '',
                [],
                null,
            ],[
                '',
                ['gzip'],
                null,
            ],
        ];
    }

    /**
     * @dataProvider encodingsProvider
     */
    public function testEncoding($accept, array $encodings, $encoding)
    {
        $response = $this->execute(
            [
                Middleware::EncodingNegotiator($encodings),
                function ($request, $response, $next) {
                    $response->getBody()->write(EncodingNegotiator::getEncoding($request));

                    return $response;
                },
            ],
            '',
            ['Accept-Encoding' => $accept]
        );

        $this->assertEquals($encoding, (string) $response->getBody());
    }
}
