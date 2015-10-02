<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils\FileTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Middleware to read the response 
 */
class ReadResponse
{
    use FileTrait;

    /**
     * Execute the middleware
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $file = $this->getFilename($request);

        if (!is_file($file)) {
            return $response->withStatus(404);
        }

        return $next($request, $response->withBody(Middleware::createStream($file)));
    }
}
