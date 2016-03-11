<?php

use Psr7Middlewares\Middleware;

class SaveReadResponseTest extends Base
{
    public function saveProvider()
    {
        return [
            [
                '/hello-world',
                '/hello-world/index.html',
                'Hello world',
            ],
            [
                '/post',
                '/post/index.html',
                'This is a post',
            ],
            [
                '/Os miúdos camiños',
                '/Os miúdos camiños/index.html',
                'This is a post with spaces and tildes',
            ],
            [
                '/index.json',
                '/index.json',
                '{"hello": "world"}',
            ],
            [
                '/',
                '/index.html',
                'Index',
            ],
        ];
    }

    /**
     * @dataProvider saveProvider
     */
    public function testSaveReadResponse($url, $file, $content)
    {
        $storage = __DIR__.'/tmp';

        $this->dispatch(
            [Middleware::saveResponse($storage)],
            $this->request($url),
            $this->response()->withBody($this->stream($content))
        );

        $this->assertTrue(is_file($storage.$file));
        $this->assertEquals($content, file_get_contents($storage.$file));

        $response = $this->execute(
            [Middleware::readResponse($storage)],
            $url
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($content, (string) $response->getBody());

        unlink($storage.$file);
        rmdir(dirname($storage.$file));
    }

    public function testContentRange()
    {
        $storage = __DIR__.'/assets';

        $response = $this->execute([
            Middleware::readResponse($storage),
            ],
            'image.png',
            ['Range' => 'bytes=300-']
        );

        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals('bytes 300-171158/171159', $response->getHeaderLine('Content-Range'));
    }

    public function testContinueOnError()
    {
        $storage = __DIR__.'/assets';

        $response = $this->execute([
                Middleware::readResponse($storage)->continueOnError(),
                function ($request, $response, $next) {
                    $response->getBody()->write('hello');

                    return $next($request, $response);
                },
            ],
            'notfound.png'
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('hello', (string) $response->getBody());
    }
}
