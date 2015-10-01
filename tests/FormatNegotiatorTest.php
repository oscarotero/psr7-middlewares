<?php
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\FormatNegotiator;

class FormatNegotiatorTest extends Base
{
    public function formatsProvider()
    {
        return [
            [
                '/',
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                'html',
            ],[
                '/test.json',
                '',
                'json'
            ],[
                '/',
                '',
                null
            ],[
                '/',
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                'html'
            ]
        ];
    }

    /**
     * @dataProvider formatsProvider
     */
    public function testTypes($url, $accept, $format)
    {
        $response = $this->execute(
            [
                Middleware::FormatNegotiator(),
                function ($request, $response, $next) {
                    $response->getBody()->write(FormatNegotiator::getFormat($request));

                    return $response;
                },
            ],
            $url,
            ['Accept' => $accept]
        );

        $this->assertEquals($format, (string) $response->getBody());
    }
}
