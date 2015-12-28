<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to gzip encode the response body
 */
class Gzip
{
    /**
     * Execute the middleware.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!Middleware::hasAttribute($request, EncodingNegotiator::KEY)) {
            throw new RuntimeException('Gzip middleware needs EncodingNegotiator executed before');
        }

        if (EncodingNegotiator::getEncoding($request) === 'gzip') {
            $compressed = Middleware::createStream();
            $compressed->write(gzencode((string) $response->getBody()));
            $response = $response
                ->withHeader('Content-Encoding', 'gzip')
                ->withBody($compressed);
        }

        return $next($request, $response);
    }
}
