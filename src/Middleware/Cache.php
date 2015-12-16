<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Cache\CacheItemPoolInterface;
use Datetime;

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
     * Constructor. Set the cache pool.
     *
     * @param CacheItemPoolInterface|null $cache
     */
    public function __construct(CacheItemPoolInterface $cache = null)
    {
        if ($cache !== null) {
            $this->cache($cache);
        }
    }

    /**
     * Set the psr-6 cache pool used.
     *
     * @param CacheItemPoolInterface $cache
     */
    public function cache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
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

        if ($item->isHit()) {
            list($headers, $body) = $item->get();

            $response = $response->withBody(Middleware::createStream());
            $response->getBody()->write($body);

            foreach ($headers as $name => $header) {
                $response = $response->withHeader($name, $header);
            }

            return $response;
        }

        $response = $next($request, $response);

        if (self::isCacheable($request, $response)) {
            $item->set([
                $response->getHeaders(),
                (string) $response->getBody(),
            ]);

            if (($time = self::getExpiration($response)) !== null) {
                $item->expiresAt($time);
            }

            $this->cache->save($item);
        }

        return $response;
    }

    /**
     * Check the cache headers and return the expiration time.
     *
     * @param ResponseInterface $response
     *
     * @return Datetime|null
     */
    private static function getExpiration(ResponseInterface $response)
    {
        //Cache-Control
        $cacheControl = $response->getHeaderLine('Cache-Control');

        if (!empty($cacheControl)) {
            $cacheControl = self::parseCacheControl($cacheControl);

            //Max age
            if (isset($cacheControl['max-age'])) {
                return new Datetime('@'.(time() + (int) $cacheControl['max-age']));
            }
        }

        //Expires
        $expires = $response->getHeaderLine('Expires');

        if (!empty($expires)) {
            return new Datetime($expires);
        }
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
