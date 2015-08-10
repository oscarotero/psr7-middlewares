<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\BasePathTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to remove the trailing slash
 */
class TrailingSlash
{
    use BasePathTrait;

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
            $path = substr($path, 0, -1);
        }

        //Ensure the path has one "/"
        if (empty($path) || $path === $this->basePath) {
            $path .= '/';
        }

        $request = $request->withUri($uri->withPath($path));

        return $next($request, $response);
    }
}
