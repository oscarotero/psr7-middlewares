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
        if (!count($request->getQueryParams()) && !static::isCacheable($request, $response)) {
            return $next($request, $response);
        }

        static::writeStream($response->getBody(), $this->getCacheFilename($request));

        return $next($request, $response);
    }

    /**
     * Returns the filename of the response cache file
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    protected function getCacheFilename(ServerRequestInterface $request)
    {
        $path = $request->getUri()->getPath();

        if (!empty($this->basePath) && strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath)) ?: '';
        }

        $parts = pathinfo($path);
        $path = '/'.(isset($parts['dirname']) ? $parts['dirname'] : '');
        $filename = isset($parts['basename']) ? $parts['basename'] : '';

        //if it's a directory, append "/index.html"
        if (empty($parts['extension'])) {
            if ($path === '/') {
                $path .= $filename;
            } else {
                $path .= '/'.$filename;
            }

            $filename = 'index.'.($request->getAttribute('FORMAT') ?: 'html');
        }

        return $this->documentRoot.$path.'/'.$filename;
    }
}
