<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to rename the uri path.
 */
class Rename
{
    /**
     * @var array Renamed paths
     */
    private $paths = [];

    /**
     * Constructor. Set the paths.
     *
     * @param array|null $paths
     */
    public function __construct(array $paths = null)
    {
        if ($paths !== null) {
            $this->paths($paths);
        }
    }

    /**
     * Map with the names.
     *
     * @param array $paths ['private-name' => 'public-name']
     *
     * @return self
     */
    public function paths(array $paths)
    {
        $this->paths = $paths;

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

        if (isset($this->paths[$path])) {
            return $response->withStatus(404);
        }

        $newPath = array_search($path, $this->paths, true);

        if ($newPath !== false) {
            $request = $request->withUri($uri->withPath($newPath));
        }

        return $next($request, $response);
    }
}
