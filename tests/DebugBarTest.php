<?php

use Psr7Middlewares\Middleware;

class DebugBarTest extends Base
{
    public function debugBarProvider()
    {
        return [
            ['', [], true],
            ['data.json', [], false],
            ['', ['X-Requested-With' => 'xmlhttprequest'], false],
        ];
    }

    /**
     * @dataProvider debugBarProvider
     */
    public function testDebugBar($uri, array $headers, $expected)
    {
        $debugBar = new DebugBar\StandardDebugBar();

        $response = $this->dispatch([
            Middleware::FormatNegotiator(),
            Middleware::DebugBar($debugBar),
        ], $this->request($uri, $headers), $this->response());

        $body = (string) $response->getBody();

        if ($expected) {
            $this->assertNotFalse(strpos($body, '<script>'));
            $this->assertNotFalse(strpos($body, '<style>'));
        } else {
            $this->assertFalse(strpos($body, '<script>'));
            $this->assertFalse(strpos($body, '<style>'));
        }
    }
}
