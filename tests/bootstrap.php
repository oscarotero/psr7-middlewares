<?php

error_reporting(E_ALL);

include_once dirname(__DIR__).'/vendor/autoload.php';
include_once __DIR__.'/Base.php';

PHPUnit_Framework_Error_Notice::$enabled = true;

/**
 * Minimal Container used for testing.
 */
class ServiceContainer implements Psr7Middlewares\ResolverInterface
{
    protected $items = [];

    public function set($id, $value)
    {
        $this->items[$id] = $value;
    }

    public function resolve($id)
    {
        return $this->items[$id];
    }

    public function has($id)
    {
        return !empty($this->items[$id]);
    }
}
