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
            $factory = "{$class}::create";

            return call_user_func_array($factory, $args);
        }

        throw new RuntimeException("The middleware {$name} does not exits");
    }
}
