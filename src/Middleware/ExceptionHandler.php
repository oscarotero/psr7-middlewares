<?php
namespace Psr7Middlewares\Middleware;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to handle exceptions
 */
class ExceptionHandler
{
    protected $handler;

    /**
     * Creates an instance of this middleware
     *
     * @param callable|null $handler
     */
    public static function create(callable $handler = null)
    {
        if (!$handler) {
            $handler = __CLASS__.'::defaultHandler';
        }

        return new static($handler);
    }

    /**
     * Constructor. Set the path prefix
     *
     * @param callable $handler
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
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
        } catch (Exception $exception) {
            $response = call_user_func($this->handler, $exception, $request, $response);
        }

        return $response;
    }

    /**
     * Default handler if none has been provided
     *
     * @param Exception              $exception
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    protected static function defaultHandler(Exception $exception, ServerRequestInterface $request, ResponseInterface $response)
    {
        $response = $response->withStatus(500);
        $response->getBody()->write($exception->getMessage());

        return $response;
    }
}
