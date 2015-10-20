<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to calculate the response time duration.
 */
class ResponseTime
{
    const HEADER = 'X-Response-Time';

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $server = $request->getServerParams();

        if (!isset($server['REQUEST_TIME_FLOAT'])) {
            $server['REQUEST_TIME_FLOAT'] = microtime(true);
        }

        $response = $next($request, $response);
        $time = (microtime(true) - $server['REQUEST_TIME_FLOAT']) * 1000;

        return $response->withHeader(self::HEADER, sprintf('%2.3fms', $time));
    }
}
