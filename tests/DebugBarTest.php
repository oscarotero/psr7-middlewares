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
            $this->assertNotFalse(strpos($body, '<script>'));
            $this->assertNotFalse(strpos($body, '<style>'));
        } else {
            $this->assertFalse(strpos($body, '<script>'));
            $this->assertFalse(strpos($body, '<style>'));
        }

        if ($expectedHeader) {
            $this->assertTrue($response->hasHeader('phpdebugbar'));
        } else {
            $this->assertFalse($response->hasHeader('phpdebugbar'));
        }
    }
}
