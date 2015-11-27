<?php

use Psr7Middlewares\Middleware;

class HoneypotTest extends Base
{
    public function htmlFormsProvider()
    {
        return [
            [
                '<form method="post"></form>',
                '<form method="post"><input type="text"></form>'
            ],[
                '<form action="" method="POST" class="form"></form>',
                '<form action="" method="POST" class="form"><input type="text"></form>'
            ],[
                '<form></form><form method=POST></form>',
                '<form></form><form method=POST><input type="text"></form>'
            ],[
                '<form></form>',
                '<form></form>'
            ],[
                '<form method="get"></form>',
                '<form method="get"></form>'
            ],[
                '<form method="POST"></form> <div><form method=POST></form></div>',
                '<form method="POST"><input type="text"></form> <div><form method=POST><input type="text"></form></div>'
            ]
        ];
    }

    /**
     * @dataProvider htmlFormsProvider
     */
    public function testHtmlForms($html, $expected)
    {
        $response = $this->execute(
            [
                Middleware::FormatNegotiator(),
                Middleware::Honeypot(),
                function ($request, $response, $next) use ($html) {
                    $response->getBody()->write($html);
                    return $next($request, $response);
                }
            ]
        );

        $this->assertEquals($expected, (string) $response->getBody());
    }
}
