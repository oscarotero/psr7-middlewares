<?php

use Psr7Middlewares\Middleware;

class CspTest extends Base
{
    public function cspProvider()
    {
        return [
            [
                null,
                true,
                "frame-ancestors 'self'; object-src 'self'; script-src 'self'; ",
            ],
            [
                [
                    'default-src' => ['self' => true],
                    'report-uri' => '/csp_violation_reporting_endpoint',
                ],
                true,
                "default-src 'self'; report-uri /csp_violation_reporting_endpoint; ",
            ],
        ];
    }

    /**
     * @dataProvider cspProvider
     */
    public function testCsp($policies, $oldBrowsers, $expected)
    {
        $response = $this->execute(
            [
                Middleware::Csp($policies)->supportOldBrowsers($oldBrowsers),
            ]
        );

        $this->assertEquals($expected, $response->getHeaderLine('Content-Security-Policy'));
    }
}
