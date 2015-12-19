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
            ],[
                '/post',
                '/post/index.html',
                'This is a post',
            ],[
                '/index.json',
                '/index.json',
                '{"hello": "world"}',
            ],[
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
            Middleware::readResponse()
                ->storage($storage)
            ],
            'image.png',
            ['Range' => 'bytes 300-500']
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('bytes 300-500/171159', $response->getHeaderLine('Content-Range'));
    }
}
