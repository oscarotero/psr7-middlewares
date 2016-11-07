<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to block robots search engine.
 */
class Robots
{
    const HEADER = 'X-Robots-Tag';

    private $allow = false;

    /**
     * Set whether search engines are allowed or not.
     *
     * @param bool $allow
     */
    public function __construct($allow = false)
    {
        $this->allow = $allow;
    }

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
        if ($request->getUri()->getPath() === '/robots.txt') {
            $response = $response->withHeader('Content-Type', 'text/plain');

            if ($this->allow) {
                $response->getBody()->write("User-Agent: *\nAllow: /");

                return $response;
            }

            $response->getBody()->write("User-Agent: *\nDisallow: /");

            return $response;
        }

        $response = $next($request, $response);

        if ($this->allow) {
            return $response->withHeader(self::HEADER, 'index, follow');
        }

        return $response->withHeader(self::HEADER, 'noindex, nofollow, noarchive');
    }
}
