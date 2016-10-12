<?php

use Psr7Middlewares\Middleware;

class MinifyTest extends Base
{
    public function testMinify()
    {
        $body = <<<'EOT'
<!DOCTYPE html>
<html>
    <head>
        <title>Title</title>
    </head>
    <body>
        <h1>Hello world!</h1>

    </body>
</html>
EOT;
        $body_minified = <<<'EOT'
<!DOCTYPE html><html><head><title>Title</title></head><body><h1>Hello world!</h1></body></html>
EOT;

        $response = $this->execute(
            [
                Middleware::FormatNegotiator(),
                Middleware::Minify(),
                function ($request, $response, $next) use ($body) {
                    $response->getBody()->write($body);

                    return $next($request, $response);
                },
            ]
        );

        $this->assertEquals($body_minified, (string) $response->getBody());
    }
}
