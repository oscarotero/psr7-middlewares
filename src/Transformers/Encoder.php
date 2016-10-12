<?php

namespace Psr7Middlewares\Transformers;

use Psr\Http\Message\StreamInterface;

/**
 * Generic resolver to encode responses.
 */
class Encoder extends Resolver
{
    protected $transformers = [
        'gzip' => [__CLASS__, 'gzip'],
        'deflate' => [__CLASS__, 'deflate'],
    ];

    /**
     * Gzip minifier using gzencode().
     *
     * @param StreamInterface $input
     * @param StreamInterface $output
     *
     * @return ResponseInterface
     */
    public static function gzip(StreamInterface $input, StreamInterface $output)
    {
        $output->write(gzencode((string) $input));

        return $output;
    }

    /**
     * Gzip minifier using gzdeflate().
     *
     * @param StreamInterface $input
     * @param StreamInterface $output
     *
     * @return ResponseInterface
     */
    public static function deflate(StreamInterface $input, StreamInterface $output)
    {
        $output->write(gzdeflate((string) $input));

        return $output;
    }
}
