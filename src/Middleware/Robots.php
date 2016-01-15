<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

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
    public function __construct(bool $allow = false)
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
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if ($request->getUri()->getPath() === '/robots.txt') {
            $body = Middleware::createStream();

            if ($this->allow) {
                $body->write("User-Agent: *\nAllow: /");
            } else {
                $body->write("User-Agent: *\nDisallow: /");
            }

            return $next($request, $response->withBody($body));
        }

        if ($this->allow) {
            return $next($request, $response->withHeader(self::HEADER, 'index, follow'));
        }

        return $next($request, $response->withHeader(self::HEADER, 'noindex, nofollow, noarchive'));
    }
}
