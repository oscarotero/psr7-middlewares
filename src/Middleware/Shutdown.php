<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Utils\RouterTrait;
use Psr7Middlewares\Utils\ArgumentsTrait;

/**
 * Middleware to display temporary 503 maintenance pages.
 */
class Shutdown
{
    use RouterTrait;
    use ArgumentsTrait;

    protected $handler;

    /**
     * Constructor.
     *
     * @param callable|string|null $handler
     */
    public function __construct($handler = null)
    {
        if ($handler !== null) {
            $this->handler($handler);
        }
    }

    /**
     * Set the shudown handler.
     *
     * @param string|callable $handler
     *
     * @return self
     */
    public function handler($handler)
    {
        $this->handler = $handler;

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
        $response = self::executeTarget($this->handler, $this->arguments, $request, $response);

        return $response->withStatus(503);
    }
}
