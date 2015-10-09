<?php

namespace Psr7Middlewares;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class Middleware
{
    const KEY = 'Psr7Middlewares\\Middleware';

    protected static $streamFactory;

    /**
     * Set the stream factory used by some middlewares.
     *
     * @param callable $streamFactory
     */
    public static function setStreamFactory(callable $streamFactory)
    {
        static::$streamFactory = $streamFactory;
    }

    /**
     * Get the stream factory.
     *
     * @return StreamInterface
     */
    public static function createStream($file = 'php://temp', $mode = 'r+')
    {
        if (empty(static::$streamFactory)) {
            if (class_exists('Zend\\Diactoros\\Stream')) {
                return new \Zend\Diactoros\Stream($file, $mode);
            }

            throw new \RuntimeException('Unable to create a stream. No stream factory defined');
        }

        return call_user_func(static::$streamFactory, $file, $mode);
    }

    /**
     * Create instances of the middlewares.
     *
     * @param string $name
     * @param array  $args
     */
    public static function __callStatic($name, $args)
    {
        $class = __NAMESPACE__.'\\Middleware\\'.ucfirst($name);

        if (class_exists($class)) {
            if (!empty($args)) {
                return (new \ReflectionClass($class))->newInstanceArgs($args);
            }

            return new $class();
        }

        throw new RuntimeException("The middleware {$name} does not exits");
    }

    /**
     * Store an attribute in the request.
     *
     * @param ServerRequestInterface $request
     * @param string                 $name
     * @param mixed                  $value
     *
     * @return ServerRequestInterface
     */
    public static function setAttribute(ServerRequestInterface $request, $name, $value)
    {
        $attributes = $request->getAttribute(self::KEY, []);
        $attributes[$name] = $value;

        return $request->withAttribute(self::KEY, $attributes);
    }

    /**
     * Retrieves an attribute from the request.
     *
     * @param ServerRequestInterface $request
     * @param string                 $name
     *
     * @return mixed
     */
    public static function getAttribute(ServerRequestInterface $request, $name)
    {
        $attributes = $request->getAttribute(self::KEY);

        if (isset($attributes[$name])) {
            return $attributes[$name];
        }
    }

    /**
     * Check whether an attribute exists.
     *
     * @param ServerRequestInterface $request
     * @param string                 $name
     *
     * @return bool
     */
    public static function hasAttribute(ServerRequestInterface $request, $name)
    {
        $attributes = $request->getAttribute(self::KEY);

        return array_key_exists($name, $attributes);
    }
}
