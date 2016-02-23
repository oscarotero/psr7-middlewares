<?php

namespace Psr7Middlewares\Storage;

use Aura\Session\Session;
use Psr7Middlewares\Middleware;

/**
 * Wrapper for Aura.Session.
 */
class AuraSession implements StorageInterface
{
    protected $storage;

    public function __construct(Session $session)
    {
        $this->storage = $session->getSegment(Middleware::KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->storage->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->storage->set($key, $value);
    }
}
