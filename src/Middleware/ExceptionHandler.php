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
    /**
     * Creates an instance of this middleware
     *
     * @return ExceptionHandler
     */
    public static function create()
    {
        return new static();
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
            $response = $response->withStatus(500);
            $response->getBody()->write($exception->getMessage());
        }

        return $response;
    }
}
