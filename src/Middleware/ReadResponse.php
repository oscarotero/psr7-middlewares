<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to read the response.
 */
class ReadResponse
{
    use Utils\FileTrait;

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
        $file = $this->getFilename($request);

        if (!is_file($file)) {
            return $response->withStatus(404);
        }

        return $next($request, $response->withBody(Middleware::createStream($file)));
    }
}
