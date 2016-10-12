<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Helper functions.
 */
class Helpers
{
    private static $hash_equals;

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

    /**
     * Check if a request is post or any similar method.
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    public static function isPost(RequestInterface $request)
    {
        switch (strtoupper($request->getMethod())) {
            case 'GET':
            case 'HEAD':
            case 'CONNECT':
            case 'TRACE':
            case 'OPTIONS':
                return false;
        }

        return true;
    }

    /**
     * Check whether a response is a redirection.
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    public static function isRedirect(ResponseInterface $response)
    {
        return in_array($response->getStatusCode(), [302, 301]);
    }

    /**
     * Return the output buffer.
     *
     * @param int $level
     *
     * @return string
     */
    public static function getOutput($level)
    {
        $output = '';

        while (ob_get_level() >= $level) {
            $output .= ob_get_clean();
        }

        return $output;
    }

    /**
     * Return the mime type.
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    public static function getMimeType(ResponseInterface $response)
    {
        $mime = strtolower($response->getHeaderLine('Content-Type'));
        $mime = explode(';', $mime, 2);

        return trim($mime[0]);
    }

    /**
     * Very short timing attack safe string comparison for PHP < 5.6
     * http://php.net/manual/en/function.hash-equals.php#118384.
     *
     * @param string $a
     * @param string $b
     *
     * @return bool
     */
    public static function hashEquals($a, $b)
    {
        if (self::$hash_equals === null) {
            self::$hash_equals = function_exists('hash_equals');
        }

        if (self::$hash_equals) {
            return hash_equals($a, $b);
        }

        return substr_count($a ^ $b, "\0") * 2 === strlen($a.$b);
    }
}
