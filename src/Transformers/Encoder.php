<?php

namespace Psr7Middlewares\Transformers;

use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Middleware;

/**
 * Generic resolver to encode responses.
 */
class Encoder extends Resolver
{
    /**
     * Gzip minifier using gzencode().
     * 
     * @param ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    public function gzip(ResponseInterface $response)
    {
        $stream = Middleware::createStream();
        $stream->write(gzencode((string) $response->getBody()));

        return $response
            ->withHeader('Content-Encoding', 'gzip')
            ->withBody($stream);
    }

    /**
     * Gzip minifier using gzdeflate().
     * 
     * @param ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    public function deflate(ResponseInterface $response)
    {
        $stream = Middleware::createStream();
        $stream->write(gzdeflate((string) $response->getBody()));

        return $response
            ->withHeader('Content-Encoding', 'deflate')
            ->withBody($stream);
    }
}
