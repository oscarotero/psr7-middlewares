<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\CacheTrait;
use Psr7Middlewares\Utils\BasePathTrait;
use Psr7Middlewares\Utils\StorageTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to save the response into a file
 */
class SaveResponse
{
    use CacheTrait;
    use BasePathTrait;
    use StorageTrait;

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
        $response = $next($request, $response);

        if (!count($request->getQueryParams()) && static::isCacheable($request, $response)) {
            static::writeStream($response->getBody(), $this->getCacheFilename($request));
        }

        return $response;
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
        $path = $this->getBasePath($request->getUri()->getPath());

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

            $filename = 'index.'.(FormatNegotiator::getFormat($request) ?: 'html');
        }

        return $this->storage.$path.'/'.$filename;
    }
}
