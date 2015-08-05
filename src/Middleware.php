<?php
namespace Psr7Middlewares;

use RuntimeException;

class Middleware
{
    /**
     * Create instances of the middlewares
     *
     * @param string $name
     * @param array  $args
     */
    public static function __callStatic($name, $args)
    {
        $class = __NAMESPACE__.'\\Middleware\\'.ucfirst($name);

        if (class_exists($class)) {
            if (isset($args[0])) {
                return new $class($args[0]);
            }

            return new $class();
        }

        throw new RuntimeException("The middleware {$name} does not exits");
    }
}
