<?php

namespace Psr7Middlewares\Utils;

/**
 * Common functions to work with paths.
 */
class Path
{
    /**
     * helper function to fix paths '//' or '/./' or '/foo/../' in a path.
     *
     * @param string $path Path to resolve
     *
     * @return string
     */
    public static function fix($path)
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
     * @param string $piece1
     * @param string $piece2
     *                       ...
     * 
     * @return string
     */
    public static function join()
    {
        return self::fix(implode('/', func_get_args()));
    }
}
