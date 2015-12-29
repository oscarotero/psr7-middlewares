<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\RequestInterface;

/**
 * Helper functions.
 */
class Helpers
{
    /**
     * helper function to fix paths '//' or '/./' or '/foo/../' in a path.
     *
     * @param string $path Path to resolve
     *
     * @return string
     */
    public static function fixPath($path)
    {
        $path = str_replace('\\', '/', $path); //windows paths
        $replace = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];

        do {
            $path = preg_replace($replace, '/', $path, -1, $n);
        } while ($n > 0);

        return $path;
    }

    /**
     * Join several pieces into a path.
     * 
     * @param string
     *               ...
     * 
     * @return string
     */
    public static function joinPath()
    {
        return self::fixPath(implode('/', func_get_args()));
    }

    /**
     * Check whether a request is or not ajax.
     * 
     * @param RequestInterface $request
     * 
     * @return bool
     */
    public static function isAjax(RequestInterface $request)
    {
        return strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }
}
