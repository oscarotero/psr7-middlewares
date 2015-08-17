<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\RouterTrait;
use Psr7Middlewares\Utils\ArgumentsTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to handle php errors and exceptions
 */
class ErrorHandler
{
    use RouterTrait;
    use ArgumentsTrait;

    protected $handler;
    protected $before;
    protected $after;

    /**
     * Constructor
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
     * Set the error handler
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
     * Register a handler executed before
     *
     * @param callable $handler
     *
     * @return self
     */
    public function before(callable $handler)
    {
        $this->before = $handler;

        return $this;
    }

    /**
     * Register a handler executed after
     *
     * @param callable $handler
     *
     * @return self
     */
    public function after(callable $handler)
    {
        $this->after = $handler;

        return $this;
    }

    /**
     * Execute the middleware
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $handler = function () use ($request, $response) {
            return self::executeTarget($this->handler, $this->arguments, $request, $response);
        };

        if ($this->before !== null) {
            call_user_func($this->before, $handler);
        }

        $response = $next($request, $response);

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            return self::executeTarget($this->handler, $this->arguments, $request, $response);
        }

        if ($this->after !== null) {
            call_user_func($this->after, $handler);
        }

        return $response;
    }
}
