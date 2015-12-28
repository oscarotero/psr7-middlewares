<?php

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Middleware\AuraRouter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class AccessLogTest extends Base
{
    public function testAccessLog()
    {
        $logs = fopen('php://temp', 'r+');
        $logger = new Logger('test');
        $logger->pushHandler(new StreamHandler($logs));

        //Test
        $response = $this->execute(
            [
                Middleware::clientIp(),
                Middleware::AccessLog($logger),
            ],
            'http://domain.com/user/oscarotero/35'
        );

        rewind($logs);
        $this->assertRegExp('#.* "GET /user/oscarotero/35 HTTP/1\.1" 200 0.*#', stream_get_contents($logs));
    }
}
