<?php

namespace Psr7Middlewares\Utils;

use Psr7Middlewares\Middleware;

/**
 * Trait used to create streams.
 */
trait StreamTrait
{
    /**
     * Get the stream factory.
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    private static function createStream($file = 'php://temp', $mode = 'r+')
    {
        $factory = Middleware::getStreamFactory();

        if ($factory === null) {
            if (class_exists('Zend\\Diactoros\\Stream')) {
                return new \Zend\Diactoros\Stream($file, $mode);
            }

            throw new \RuntimeException('Unable to create a stream. No stream factory defined');
        }

        return call_user_func($factory, $file, $mode);
    }
}
