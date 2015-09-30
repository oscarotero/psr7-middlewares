<?php
use Psr7Middlewares\Middleware;

class MinifyTest extends Base
{
    public function testMinify()
    {
        $body = <<<EOT
<!DOCTYPE html>
<html>
    <head>
        <title>Title</title>

        <style type="text/css">
            .is-red {
                color: red;
            }
        </style>
    </head>
    <body>
        <h1>Hello world!</h1>

        <script type="text/javascript">
            document.querySelector('h1').className = 'is-red';
        </script>
    </body>
</html>
EOT;
        $body_minified = <<<EOT
<!DOCTYPE html><html><head><title>Title</title><style type="text/css">.is-red{color:red}</style></head><body><h1>Hello world!</h1> <script type="text/javascript">document.querySelector('h1').className='is-red'</script> </body></html>
EOT;

        $response = $this->response(['Content-Type' => 'text/html']);
        $response->getBody()->write($body);

        $middlewares = [
            Middleware::Minify(),
        ];

        $response = $this->dispatch($middlewares, $this->request(), $response);

        $this->assertEquals($body_minified, (string) $response->getBody());
    }
}
