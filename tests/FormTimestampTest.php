<?php

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils\CryptTrait;

class FormTimestampTest extends Base
{
    use CryptTrait;

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
        $this->key(hex2bin('000102030405060708090a0b0c0d0e0f'));

        $response = $this->dispatch(
            [
                Middleware::formatNegotiator(),
                Middleware::formTimestamp()
                    ->key(hex2bin('000102030405060708090a0b0c0d0e0f'))
                    ->min($min)
                    ->max($max),
            ],
            $this->request()->withMethod('post')->withParsedBody(['hpt_time' => $this->encrypt(time() - $duration)]),
            $this->response()
        );

        if ($success) {
            $this->assertEquals(200, $response->getStatusCode());
        } else {
            $this->assertEquals(403, $response->getStatusCode());
        }
    }
}
