<?php

use Psr7Middlewares\Middleware;

class GoogleAnalyticsTest extends Base
{
    public function GoogleAnalyticsProvider()
    {
        return [
            ['', [], true],
            ['data.json', [], false],
            ['', ['X-Requested-With' => 'xmlhttprequest'], false],
        ];
    }

    /**
     * @dataProvider GoogleAnalyticsProvider
     */
    public function testDebugBar($uri, array $headers, $expected)
    {
        $response = $this->dispatch([
            Middleware::FormatNegotiator(),
            Middleware::GoogleAnalytics('UA-XXXXX-X'),
        ], $this->request($uri, $headers), $this->response());

        $body = (string) $response->getBody();

        if ($expected) {
            $this->assertNotFalse(strpos($body, '<script>'));
            $this->assertNotFalse(strpos($body, 'UA-XXXXX-X'));
        } else {
            $this->assertFalse(strpos($body, '<script>'));
            $this->assertFalse(strpos($body, 'UA-XXXXX-X'));
        }
    }
}
