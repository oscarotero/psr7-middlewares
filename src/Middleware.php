<?php
namespace Psr7Middlewares;

use Psr\Http\Message\Stream;
use RuntimeException;

class Middleware
{
    protected static $streamFactory;

    /**
     * Set the stream factory used by some middlewares
     * 
     * @param callable $streamFactory
     */
    public static function setStreamFactory(callable $streamFactory)
    {
        static::$streamFactory = $streamFactory;
    }

    /**
     * Get the stream factory
     * 
     * @return Stream
     */
    public static function createStream($file = 'php://temp', $mode = 'r+')
    {
        return call_user_func(static::$streamFactory, $file, $mode);
    }

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
