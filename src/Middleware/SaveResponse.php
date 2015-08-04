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

    /**
     * Creates an instance of this middleware
     *
     * @param string $documentRoot
     *
     * @return SaveResponse
     */
    public static function create($documentRoot = '')
    {
        return new static($documentRoot);
    }

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

        static::writeFile($response->getBody(), $this->documentRoot.static::getCacheFilename($request));

        return $next($request, $response);
    }
}
