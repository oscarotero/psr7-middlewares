<?php

use Psr7Middlewares\Middleware;

class RobotsTest extends Base
{
    public function testRobotsHeader()
    {
        $response = $this->execute([Middleware::Robots()]);

        $this->assertSame('noindex, nofollow, noarchive', $response->getHeaderLine('X-Robots-Tag'));
    }

    public function testRobotsTxt()
    {
        $response = $this->execute([Middleware::Robots()], '/robots.txt');

        $this->assertSame("User-Agent: *\nDisallow: /", (string) $response->getBody());
    }
}
