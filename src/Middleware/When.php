<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to add other middleware under a condition.
 */
class When
{
    /**
     * @var callable|null The middleware to add
     */
    protected $middleware;

    /**
     * @var mixed The condition
     */
    protected $condition;

    /**
     * Constructor.
     *
     * @param mixed $condition
     */
    public function __construct($condition = null, $middleware = null)
    {
        $this->condition($condition);

        if ($middleware !== null) {
            $this->middleware($middleware);
        }
    }

    /**
     * Set the condition.
     *
     * @param mixed $condition
     *
     * @return self
     */
    public function condition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Set the middleware.
     *
     * @param callable $middleware
     *
     * @return self
     */
    public function middleware(callable $middleware)
    {
        $this->middleware = $middleware;

        return $this;
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
        $condition = $this->condition;

        if (is_callable($condition)) {
            $condition = $condition($request, $response);
        }

        if (empty($condition)) {
            return $next($request, $response);
        }

        return call_user_func($this->middleware, $request, $response, $next);
    }
}
