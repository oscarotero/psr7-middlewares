<?php
namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to strip the path prefix
 */
class BasePath
{
    protected $prefix;

    /**
     * Creates an instance of this middleware
     * 
     * @param string $prefix
     */
    public static function create($prefix)
    {
        return new static($prefix);
    }

    /**
     * Constructor. Set the path prefix
     *
     * @param string $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
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

        if (strpos($path, $this->prefix) === 0) {
            $path = substr($path, strlen($this->prefix));
            $request = $request->withUri($uri->withPath($path));
        }

        return $next($request, $response);
    }
}
