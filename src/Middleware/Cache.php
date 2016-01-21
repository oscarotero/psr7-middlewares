<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Cache\CacheItemPoolInterface;
use Datetime;
use Micheh\Cache\CacheUtil;
use Micheh\Cache\Header\CacheControl;

/**
 * Middleware to cache the response using Cache-Control and other directives.
 */
class Cache
{
    use Utils\CacheTrait;

    /**
     * @var CacheItemPoolInterface The cache implementation used
     */
    private $cache;

    /**
     * @var CacheUtil
     */
    private $cacheUtil;

    /**
     * @var CacheControl
     */
    private $cacheControl;

    /**
     * Set the psr-6 cache pool.
     *
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
        $this->cacheUtil = new CacheUtil();
    }

    /**
     * Set a cache-control header to all responses
     * 
     * @param string|CacheControl $cacheControl
     * 
     * @return self
     */
    public function cacheControl($cacheControl)
    {
        if (!($cacheControl instanceof CacheControl)) {
            $cacheControl = CacheControl::fromString($cacheControl);
        }

        $this->cacheControl = $cacheControl;

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
        $item = $this->cache->getItem(self::getCacheKey($request));

        //If it's cached
        if ($item->isHit()) {
            $headers = $item->get();

            foreach ($headers as $name => $header) {
                $response = $response->withHeader($name, $header);
            }

            if ($this->cacheUtil->isNotModified($request, $response)) {
                return $response->withStatus(304);
            }

            return $response;
        }

        $response = $next($request, $response);

        //Add cache-control header
        if ($this->cacheControl && !$response->hasHeader('Cache-Control')) {
            $response = $this->cacheUtil->withCacheControl($response, $this->cacheControl);
        }

        //Save in the cache
        if ($this->cacheUtil->isCacheable($response)) {
            $item->set($response->getHeaders());

            $time = $this->cacheUtil->getLifetime($response);

            if ($time) {
                $item->expiresAt(time() + $time);
            }

            $this->cache->save($item);
        }

        return $response;
    }

    /**
     * Returns the id used to cache a request.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    private function getCacheKey(RequestInterface $request)
    {
        return $request->getMethod().md5((string) $request->getUri());
    }
}
