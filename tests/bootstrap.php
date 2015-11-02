<?php

error_reporting(E_ALL);

include_once dirname(__DIR__).'/vendor/autoload.php';
include_once __DIR__.'/Base.php';

PHPUnit_Framework_Error_Notice::$enabled = true;

/**
 * Minimal Container used for testing.
 */
class ServiceContainer implements Interop\Container\ContainerInterface
{
    protected $items = [];

    public function set($id, $value)
    {
        $this->items[$id] = $value;
    }

    public function get($id)
    {
        return call_user_func($this->items[$id], $this);
    }

    public function has($id)
    {
        return !empty($this->items[$id]);
    }
}
