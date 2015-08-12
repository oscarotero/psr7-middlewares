<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\RouterTrait;
use Psr7Middlewares\Utils\ArgumentsTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ErrorResponseHandler
{
    use RouterTrait;
    use ArgumentsTrait;

    protected $handler;

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
     * Configure the error handler
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
     * Execute the middleware
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            $response = $next($request, $response);
        } catch (\Exception $exception) {
            $request = $request->withAttribute('EXCEPTION', $exception);
            $response = $response->withStatus(500);
        }

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            return self::executeTarget($this->handler, $this->arguments, $request, $response);
        }

        return $response;
    }
}
