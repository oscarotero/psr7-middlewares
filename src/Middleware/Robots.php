<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to block robots search engine.
 */
class Robots
{
    const HEADER = 'X-Robots-Tag';

    private $allowIndex = false;

    /**
     * Allow index
     * 
     * @param bool $allowIndex
     * 
     * @return self
     */
    public function allowIndex($allowIndex = true)
    {
        $this->allowIndex = $allowIndex;

        return $this;
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
            $body = Middleware::createStream();

            if ($this->allowIndex) {
                $body->write("User-Agent: *\nAllow: /");
            } else {
                $body->write("User-Agent: *\nDisallow: /");
            }

            return $next($request, $response->withBody($body));
        }

        if ($this->allowIndex) {
            return $next($request, $response->withHeader(self::HEADER, 'index, follow'));
        }
        
        return $next($request, $response->withHeader(self::HEADER, 'noindex, nofollow, noarchive'));
    }
}
