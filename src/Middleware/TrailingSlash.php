<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
     * Constructor. Configure whether add or remove the slash.
     *
     * @param bool $addSlash
     */
    public function __construct($addSlash = false)
    {
        $this->addSlash($addSlash);
    }

    /**
     * Configure whether the slash should be added or removed.
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
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
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
