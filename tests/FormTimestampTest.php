<?php

use Psr7Middlewares\Middleware;

class FormTimestampTest extends Base
{
    public function timesProvider()
    {
        return [
            [3, 10, 7, true],
            [3, 10, 11, false],
            [3, 10, 1, false],
            [3, 0, 1, false],
            [3, 0, 1000, true],
            [3, 0, 0, false],
            [0, 0, 0, true],
        ];
    }

    /**
     * @dataProvider timesProvider
     */
    public function testTimes($min, $max, $duration, $success)
    {
        $response = $this->dispatch(
            [
                Middleware::formatNegotiator(),
                Middleware::formTimestamp()
                    ->min($min)
                    ->max($max),
            ],
            $this->request()->withMethod('post')->withParsedBody(['hpt_time' => time() - $duration]),
            $this->response()
        );

        if ($success) {
            $this->assertEquals(200, $response->getStatusCode());
        } else {
            $this->assertEquals(403, $response->getStatusCode());
        }
    }
}
