<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\CacheTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to save the response into a file
 */
class SaveResponse
{
    use CacheTrait;

    protected $documentRoot;
    protected $basePath;

    /**
     * Constructor. Set the document root
     *
     * @param string $documentRoot
     */
    public function __construct($documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }

    /**
     * Set the basepath used in the request
     *
     * @param string $basePath
     * 
     * @return self
     */
    public function basePath($basePath)
    {
        $this->basePath = $basePath;

        return $this;
    }

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
        if (!static::isCacheable($request, $response)) {
            return $next($request, $response);
        }

        static::writeFile($response->getBody(), $this->documentRoot.static::getCacheFilename($request, $this->basePath));

        return $next($request, $response);
    }
}
