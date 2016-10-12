<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\MessageInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Trait used by all middlewares that needs to a psr-7 message into a psr-6 cache.
 */
trait CacheMessageTrait
{
    /**
     * @var CacheItemPoolInterface The cache implementation used
     */
    private $cache;

    /**
     * Provide the cache implementation.
     *
     * @param CacheItemPoolInterface $cache
     *
     * @return self
     */
    public function cache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Restore a message from the cache.
     *
     * @param string           $key     The message key
     * @param MessageInterface $message
     *
     * @return MessageInterface|false
     */
    private function getFromCache($key, MessageInterface $message)
    {
        if ($this->cache) {
            $item = $this->cache->getItem($key);

            if ($item->isHit()) {
                list($headers, $body) = $item->get();

                foreach ($headers as $name => $header) {
                    $message = $message->withHeader($name, $header);
                }

                $message->getBody()->write($body);

                return $message;
            }
        }

        return false;
    }

    /**
     * Store a message in the cache.
     *
     * @param string           $key     The message key
     * @param MessageInterface $message
     */
    private function saveIntoCache($key, MessageInterface $message)
    {
        if ($this->cache) {
            $item = $this->cache->getItem($key);

            $item->set([
                $message->getHeaders(),
                (string) $message->getBody(),
            ]);

            $this->cache->save($item);
        }
    }
}
