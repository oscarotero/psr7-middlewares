<?php

namespace Psr7Middlewares\Utils;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\StreamInterface;

/**
 * Trait used to create streams.
 */
trait StreamTrait
{
    /**
     * Get the stream factory.
     *
     * @param string|StreamInterface $file Filename or stream it's replacing
     * @param string                 $mode
     *
     * @return StreamInterface
     */
    private static function createStream($file = 'php://temp', $mode = 'r+')
    {
        $factory = Middleware::getStreamFactory();
        $replacing = null;

        if ($file instanceof StreamInterface) {
            $replacing = $file;
            $file = 'php://temp';
        }

        if ($factory === null) {
            if (class_exists('Zend\\Diactoros\\Stream')) {
                return new \Zend\Diactoros\Stream($file, $mode);
            }

            throw new \RuntimeException('Unable to create a stream. No stream factory defined');
        }

        return call_user_func($factory, $file, $mode, $replacing);
    }
}
