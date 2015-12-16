<?php

namespace Psr7Middlewares;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
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
            switch (count($args)) {
                case 0:
                    return new $class();

                case 1:
                    return new $class($args[0]);

                default:
                    return (new \ReflectionClass($class))->newInstanceArgs($args);
            }
        }

        throw new RuntimeException("The middleware {$name} does not exits");
    }

    /**
     * Create a middleware callable that acts as a "proxy" to a real middleware that must be returned by the given callback.
     *
     * @param callable $factory Takes no argument and MUST return a middleware callable
     * 
     * @return callable
     */
    public static function middleware(callable $factory)
    {
        return function(ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($factory) {
            $middleware = $factory();

            if (!is_callable($middleware)) {
                throw new RuntimeException(sprintf('Factory returned "%s" instead of a callable.', gettype($middleware)));
            }

            return $middleware($request, $response, $next);
        };
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

        if (empty($attributes)) {
            return false;
        }

        return array_key_exists($name, $attributes);
    }
}
