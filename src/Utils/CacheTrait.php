<?php
namespace Psr7Middlewares\Utils;

use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Utilities used by router middlewares
 */
trait CacheTrait
{
    /**
     * Check whether the response can be cached or not
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return boolean
     */
    protected static function isCacheable(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        //Do not cache requests with query parameters
        if (count($request->getQueryParams())) {
            return false;
        }

        //Check http headers
        $cacheHeader = $response->getHeaderLine('Cache-Control');

        if (stripos($cacheHeader, 'no-cache') !== false) {
            return false;
        }

        return true;
    }

    /**
     * Returns the filename of the response cache file
     *
     * @param ServerRequestInterface $request
     *
     * @return boolean
     */
    protected static function getCacheFilename(ServerRequestInterface $request)
    {
        $parts = pathinfo($request->getUri()->getPath());
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

        return $path.'/'.$filename;
    }
}
