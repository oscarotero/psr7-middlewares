<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

/**
 * Middleware to add or remove the trailing slash.
 */
class TrailingSlash
{
    use Utils\BasePathTrait;
    use Utils\RedirectTrait;

    /**
     * @var bool Add or remove the slash
     */
    private $addSlash;

    /**
     * Configure whether add or remove the slash.
     *
     * @param bool $addSlash
     */
    public function __construct(bool $addSlash = false)
    {
        $this->addSlash = (boolean) $addSlash;
    }

    /**
     * Execute the middleware.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        //Test basePath
        if (!$this->testBasePath($path)) {
            return $next($request, $response);
        }

        //Add/remove slash
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

        //redirect
        if (is_int($this->redirectStatus) && ($uri->getPath() !== $path)) {
            return self::getRedirectResponse($this->redirectStatus, $uri->withPath($path), $response);
        }

        return $next($request->withUri($uri->withPath($path)), $response);
    }
}
