<?php

namespace Psr7Middlewares\Storage;

/**
 * Interface used by all storage implementations
 */
interface StorageInterface
{
    /**
     * Returns the value of a segment
     * 
     * @param string $key
     * 
     * @return mixed
     */
    public function get($key);

    /**
     * Add or modify a value in a segment
     * 
     * @param string $key
     * @param string $value
     * 
     * @return mixed
     */
    public function set($key, $value);
}
