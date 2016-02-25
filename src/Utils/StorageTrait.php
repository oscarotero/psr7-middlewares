<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\ServerRequestInterface;
use Psr7Middlewares\Middleware;
use RuntimeException;

/**
 * Trait to save middleware related things in the current session.
 */
trait StorageTrait
{
    use AttributeTrait;

    /**
     * Init the storage array.
     *
     * @param ServerRequestInterface $request
     * @param array                  $storage
     * 
     * @return ServerRequestInterface
     */
    private static function initStorage(ServerRequestInterface $request, array $storage)
    {
        return self::setAttribute($request, Middleware::STORAGE_KEY, $storage);
    }

    /**
     * Returns the value of a storage array.
     *
     * @param ServerRequestInterface $request
     * @param string|null            $key
     * 
     * @return mixed
     */
    private static function getStorage(ServerRequestInterface $request, $key = null)
    {
        if (!self::hasAttribute($request, Middleware::STORAGE_KEY)) {
            throw new RuntimeException('No session storage initialized');
        }

        $storage = self::getAttribute($request, Middleware::STORAGE_KEY);

        if ($key === null) {
            return $storage;
        }

        return isset($storage[$key]) ? $storage[$key] : null;
    }

    /**
     * Returns the value of a storage array.
     *
     * @param ServerRequestInterface $request
     * @param string                 $key
     * @param mixed                  $value
     * 
     * @return ServerRequestInterface
     */
    private static function setStorage(ServerRequestInterface $request, $key, $value)
    {
        if (!self::hasAttribute($request, Middleware::STORAGE_KEY)) {
            throw new RuntimeException('No session storage initialized');
        }

        $storage = self::getAttribute($request, Middleware::STORAGE_KEY);
        $storage[$key] = $value;

        return self::setAttribute($request, Middleware::STORAGE_KEY, $storage);
    }
}
