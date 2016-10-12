<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to simulate delay in responses.
 */
class Delay
{
    protected $seconds;

    /**
     * Set the seconds to delay.
     *
     * @param int|array $seconds Use an array to random values [min, max]
     */
    public function __construct($seconds = [1, 2])
    {
        $this->seconds = $seconds;
    }

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
        $ms = $this->seconds;

        if (is_array($ms)) {
            $ms = rand(round($ms[0] * 1000000), round($ms[1] * 1000000));
        } else {
            $ms = round($ms * 1000000);
        }

        usleep($ms);

        return $next($request, $response);
    }
}
