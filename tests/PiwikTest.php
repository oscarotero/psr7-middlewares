<?php

use Psr7Middlewares\Middleware;

class PiwikTest extends Base
{
    public function PiwikProvider()
    {
        return [
            ['', [], true],
            ['data.json', [], false],
            ['', ['X-Requested-With' => 'xmlhttprequest'], false],
        ];
    }

    /**
     * @dataProvider PiwikProvider
     */
    public function testPiwik($uri, array $headers, $expected)
    {
        $response = $this->dispatch([
            Middleware::FormatNegotiator(),
            Middleware::Piwik('http://example.com/piwik'),
        ], $this->request($uri, $headers), $this->response());

        $body = (string) $response->getBody();

        if ($expected) {
            $this->assertNotFalse(strpos($body, '<script>'));
            $this->assertNotFalse(strpos($body, 'var u="http://example.com/piwik/";'));
        } else {
            $this->assertFalse(strpos($body, '<script>'));
            $this->assertFalse(strpos($body, 'var u="http://example.com/piwik/";'));
        }
    }
}
