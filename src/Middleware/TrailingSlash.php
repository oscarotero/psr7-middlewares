<?php
namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to remove the trailing slash
 */
class TrailingSlash
{
    /**
     * Creates an instance of this middleware
     *
     * @return TrailingSlash
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
        $uri = $request->getUri();
        $path = $uri->getPath();

        if (strlen($path) > 1 && substr($path, -1) === '/') {
            $request = $request->withUri($uri->withPath(substr($path, 0, -1)));
        }

        return $next($request, $response);
    }
}
