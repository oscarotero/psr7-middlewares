<?php

use Psr7Middlewares\Middleware;

class DebugBarTest extends Base
{
    public function debugBarProvider()
    {
        return [
            ['', [], true, false],
            ['data.json', [], false, false],
            ['', ['X-Requested-With' => 'xmlhttprequest'], true, false],
            ['data.json', ['X-Requested-With' => 'xmlhttprequest'], false, true],
        ];
    }

    /**
     * @dataProvider debugBarProvider
     */
    public function testDebugBar($uri, array $headers, $expectedBody, $expectedHeader)
    {
        $response = $this->dispatch([
            Middleware::FormatNegotiator(),
            Middleware::DebugBar()->captureAjax(),
        ], $this->request($uri, $headers), $this->response());

        $body = (string) $response->getBody();

        if ($expectedBody) {
            $this->assertNotFalse(strpos($body, '</script>'));
        } else {
            $this->assertFalse(strpos($body, '</script>'));
        }

        if ($expectedHeader) {
            $this->assertTrue($response->hasHeader('phpdebugbar'));
        } else {
            $this->assertFalse($response->hasHeader('phpdebugbar'));
        }
    }

    public function testAssets()
    {
        $file = '/vendor/maximebf/debugbar/src/DebugBar/Resources/vendor/highlightjs/styles/github.css';

        $response = $this->execute(
            [
                Middleware::basePath('/basepath'),
                Middleware::FormatNegotiator(),
                Middleware::DebugBar(),
            ],
            '/basepath'.$file
        );

        $content = file_get_contents(dirname(__DIR__).$file);

        $this->assertEquals($content, (string) $response->getBody());
    }
}
