<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\BasePathTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to add or remove the trailing slash.
 */
class TrailingSlash
{
    use BasePathTrait;

    protected $addSlash;

    /**
     * Constructor. Configure whether add or remove the slash.
     *
     * @param bool $addSlash
     */
    public function __construct($addSlash = false)
    {
        $this->addSlash($addSlash);
    }

    /**
     * Configure whether the path should be added or removed.
     *
     * @param bool $addSlash
     *
     * @return self
     */
    public function addSlash($addSlash)
    {
        $this->addSlash = (boolean) $addSlash;

        return $this;
    }

    /**
     * Execute the middleware.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if ($this->addSlash) {
            if (strlen($path) > 1 && substr($path, -1) !== '/' && !pathinfo($path, PATHINFO_EXTENSION)) {
                $path .= '/';
            }
        } else {
            if (strlen($path) > 1 && substr($path, -1) === '/') {
                $path = substr($path, 0, -1);
            }
        }

        //Ensure the path has one "/"
        if (empty($path) || $path === $this->basePath) {
            $path .= '/';
        }

        $request = $request->withUri($uri->withPath($path));

        return $next($request, $response);
    }
}
