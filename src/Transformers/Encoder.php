<?php declare(strict_types=1);

namespace Psr7Middlewares\Transformers;

use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Middleware;

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
     * @param ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    public static function gzip(ResponseInterface $response): ResponseInterface
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
    public static function deflate(ResponseInterface $response): ResponseInterface
    {
        $stream = Middleware::createStream();
        $stream->write(gzdeflate((string) $response->getBody()));

        return $response
            ->withHeader('Content-Encoding', 'deflate')
            ->withBody($stream);
    }
}
