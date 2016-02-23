<?php

namespace Psr7Middlewares\Storage;

use Psr7Middlewares\Middleware;

/**
 * Wrapper for PHP sessions
 */
class PhpSession implements StorageInterface
{
    protected $storage;

    public function __construct(array &$storage)
    {
        if (!isset($storage[Middleware::KEY])) {
            $storage[Middleware::KEY] = [];
        }

        $this->storage =& $storage[Middleware::KEY];
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return isset($this->storage[$key]) ? $this->storage[$key] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->storage[$key] = $value;
    }
}
