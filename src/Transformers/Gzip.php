<?php

namespace Psr7Middlewares\Transformers;

use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Middleware;

/**
 * Generic resolver to gzip compression
 */
class Gzip extends Resolver
{
    /**
     * Gzip minifier using gzencode()
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
}
