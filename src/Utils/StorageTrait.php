<?php
namespace Psr7Middlewares\Utils;

/**
 * Utilities used by middlewares with storage options
 */
trait StorageTrait
{
    protected $storage;

    /**
     * Constructor. Set the storage option
     *
     * @param string $storage
     */
    public function __construct($storage = '')
    {
        $this->storage($storage);
    }

    /**
     * Configure the storage
     *
     * @param string $storage
     *
     * @return self
     */
    public function storage($storage)
    {
        $this->storage = $storage;

        return $this;
    }
}
