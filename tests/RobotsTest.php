<?php

use Psr7Middlewares\Middleware;

class RobotsTest extends Base
{
    public function testRobotsHeader()
    {
        $response = $this->execute([Middleware::Robots()]);

        $this->assertSame('noindex, nofollow, noarchive', $response->getHeaderLine('X-Robots-Tag'));

        $response = $this->execute([Middleware::Robots()->allowIndex()]);

        $this->assertSame('index, follow', $response->getHeaderLine('X-Robots-Tag'));
    }

    public function testRobotsTxt()
    {
        $response = $this->execute([Middleware::Robots()], '/robots.txt');

        $this->assertSame("User-Agent: *\nDisallow: /", (string) $response->getBody());

        $response = $this->execute([Middleware::Robots()->allowIndex()], '/robots.txt');

        $this->assertSame("User-Agent: *\nAllow: /", (string) $response->getBody());
    }
}
