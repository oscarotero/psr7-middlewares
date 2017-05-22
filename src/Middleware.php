<?php

namespace Psr7Middlewares;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use InvalidArgumentException;

class Middleware
{
    const KEY = 'Psr7Middlewares\\Middleware';
    const STORAGE_KEY = 'STORAGE_KEY';

    private static $streamFactory;
    private static $namespaces = ['Psr7Middlewares\\Middleware\\'];

    /**
     * Register a new namespace.
     *
     * @param string $namespace
     * @param bool   $prepend
     */
    public static function registerNamespace($namespace, $prepend = false)
    {
        if (false === $prepend) {
            self::$namespaces[] = $namespace;
        } else {
            array_unshift(self::$namespaces, $namespace);
        }
    }

    /**
     * Set the stream factory used by some middlewares.
     *
     * @param callable $streamFactory
     */
    public static function setStreamFactory(callable $streamFactory)
    {
        self::$streamFactory = $streamFactory;
    }

    /**
     * Set the stream factory used by some middlewares.
     *
     * @param callable|null
     */
    public static function getStreamFactory()
    {
        return self::$streamFactory;
    }

    /**
     * Create instances of the middlewares.
     *
     * @param string $name
     * @param array  $args
     */
    public static function __callStatic($name, $args)
    {
        foreach (self::$namespaces as $namespace) {
            $class = $namespace.ucfirst($name);

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
        }

        throw new RuntimeException("The middleware {$name} does not exits");
    }

    /**
     * Create a middleware callable that acts as a "proxy" to a real middleware that must be returned by the given callback.
     *
     * @param callable|string $basePath The base path in which the middleware is created (optional)
     * @param callable        $factory  Takes no argument and MUST return a middleware callable or false
     *
     * @return callable
     */
    public static function create($basePath, callable $factory = null)
    {
        if ($factory === null) {
            $factory = $basePath;
            $basePath = '';
        }

        if (!is_callable($factory)) {
            throw new InvalidArgumentException('Invalid callable provided');
        }

        return function (RequestInterface $request, ResponseInterface $response, callable $next) use ($basePath, $factory) {
            $path = rtrim($request->getUri()->getPath(), '/');
            $basePath = rtrim($basePath, '/');

            if ($path === $basePath || strpos($path, "{$basePath}/") === 0) {
                $middleware = $factory($request, $response);
            } else {
                $middleware = false;
            }

            if ($middleware === false) {
                return $next($request, $response);
            }

            if (!is_callable($middleware)) {
                throw new RuntimeException(sprintf('Factory returned "%s" instead of a callable or FALSE.', gettype($middleware)));
            }

            return $middleware($request, $response, $next);
        };
    }
}
