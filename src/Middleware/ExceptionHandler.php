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
    protected $streamCreator;

    /**
     * Constructor
     *
     * @param callable $streamCreator
     */
    public function __construct(callable $streamCreator)
    {
        $this->streamCreator = $streamCreator;
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
            $stream = call_user_func($this->streamCreator);
            $stream->write($exception->getMessage());

            return $response->withStatus(500)->withBody($stream);
        }

        return $response;
    }
}
