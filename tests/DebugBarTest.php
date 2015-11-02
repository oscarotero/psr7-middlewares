<?php

use Psr7Middlewares\Middleware;

class DebugBarTest extends Base
{
    public function testDebugBar()
    {
        $debugBar = new DebugBar\StandardDebugBar();

        $response = $this->execute([
            Middleware::FormatNegotiator(),
            Middleware::DebugBar($debugBar),
        ]);

        $body = (string) $response->getBody();

        $this->assertNotFalse(strpos($body, '<script>'));
        $this->assertNotFalse(strpos($body, '<style>'));
    }

    public function testFormat()
    {
        $container = new ServiceContainer();
        $container->set('debugbar', new DebugBar\StandardDebugBar());

        $response = $this->execute([
            Middleware::FormatNegotiator(),
            Middleware::DebugBar()->from($container, 'debugbar'),
        ], 'data.json');

        $body = (string) $response->getBody();

        $this->assertFalse(strpos($body, '<script>'));
        $this->assertFalse(strpos($body, '<style>'));
    }
}
