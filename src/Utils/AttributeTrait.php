<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\ServerRequestInterface;
use Psr7Middlewares\Middleware;

/**
 * Trait to save middleware related things as request attributes.
 */
trait AttributeTrait
{
    /**
     * Store an attribute in the request.
     *
     * @param ServerRequestInterface $request
     * @param string                 $name
     * @param mixed                  $value
     *
     * @return ServerRequestInterface
     */
    private static function setAttribute(ServerRequestInterface $request, $name, $value)
    {
        $attributes = $request->getAttribute(Middleware::KEY, []);
        $attributes[$name] = $value;

        return $request->withAttribute(Middleware::KEY, $attributes);
    }

    /**
     * Retrieves an attribute from the request.
     *
     * @param ServerRequestInterface $request
     * @param string                 $name
     * @param mixed|null             $default
     *
     * @return mixed|null
     */
    private static function getAttribute(ServerRequestInterface $request, $name, $default = null)
    {
        $attributes = $request->getAttribute(Middleware::KEY, []);

        if (isset($attributes[$name])) {
            return $attributes[$name];
        }

        return $default;
    }

    /**
     * Check whether an attribute exists.
     *
     * @param ServerRequestInterface $request
     * @param string                 $name
     *
     * @return bool
     */
    private static function hasAttribute(ServerRequestInterface $request, $name)
    {
        $attributes = $request->getAttribute(Middleware::KEY);

        if (empty($attributes)) {
            return false;
        }

        return array_key_exists($name, $attributes);
    }
}
