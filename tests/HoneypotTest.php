<?php

use Psr7Middlewares\Middleware;

class HoneypotTest extends Base
{
    public function htmlFormsProvider()
    {
        return [
            [
                '<form method="post"></form>',
                '<form method="post"><input type="text" name="hpt_name" class="hpt_input"></form>',
            ], [
                '<form action="" method="POST" class="form"></form>',
                '<form action="" method="POST" class="form"><input type="text" name="hpt_name" class="hpt_input"></form>',
            ], [
                '<form></form><form method=POST></form>',
                '<form></form><form method=POST><input type="text" name="hpt_name" class="hpt_input"></form>',
            ], [
                '<form></form><form method=\'POST\'></form>',
                '<form></form><form method=\'POST\'><input type="text" name="hpt_name" class="hpt_input"></form>',
            ], [
                '<form></form>',
                '<form></form>',
            ], [
                '<form method="get"></form>',
                '<form method="get"></form>',
            ], [
                '<form method="POST"></form> <div><form method=POST></form></div>',
                '<form method="POST"><input type="text" name="hpt_name" class="hpt_input"></form> <div><form method=POST><input type="text" name="hpt_name" class="hpt_input"></form></div>',
            ],
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
                Middleware::Honeypot()->autoInsert(),
                function ($request, $response, $next) use ($html) {
                    $response->getBody()->write($html);

                    return $next($request, $response);
                },
            ]
        );

        $this->assertEquals($expected, (string) $response->getBody());
    }

    public function validationProvider()
    {
        return [
            ['POST', ['hpt_name' => 'not-null'], false],
            ['GET', ['hpt_name' => 'not-null'], true],
            ['POST', ['hpt_name' => ''], true],
            ['POST', ['hpt_name' => 0], false],
            ['POST', ['hpt_name' => null], false],
            ['POST', [], false],
            ['GET', [], true],
        ];
    }

    /**
     * @dataProvider validationProvider
     */
    public function testVality($method, array $data, $valid)
    {
        $request = $this->request()->withParsedBody($data)->withMethod($method);

        $response = $this->dispatch([
            Middleware::FormatNegotiator(),
            Middleware::Honeypot(),
        ], $request, $this->response());

        if ($valid) {
            $this->assertEquals(200, $response->getStatusCode());
        } else {
            $this->assertEquals(403, $response->getStatusCode());
        }
    }
}
