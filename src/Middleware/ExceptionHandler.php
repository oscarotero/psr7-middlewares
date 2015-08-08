<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware as Factory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Exception;

/**
 * Middleware to handle exceptions
 */
class ExceptionHandler
{
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
            $stream = Factory::createStream();
            $stream->write($exception->getMessage());

            return $response->withStatus(500)->withBody($stream);
        }

        return $response;
    }
}
