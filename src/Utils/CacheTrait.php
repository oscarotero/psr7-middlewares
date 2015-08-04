<?php
namespace Psr7Middlewares\Utils;

use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Utilities used by router middlewares
 */
trait CacheTrait
{
    /**
     * Check whether the response can be cached or not
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return boolean
     */
    protected static function isCacheable(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        //Do not cache requests with query parameters
        if (count($request->getQueryParams())) {
            return false;
        }

        //Check http headers
        $cache = static::parseCacheControl($response->getHeaderLine('Cache-Control'));

        if (in_array('no-cache', $cache)) {
            return false;
        }

        if (in_array('private', $cache)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the filename of the response cache file
     *
     * @param ServerRequestInterface $request
     *
     * @return boolean
     */
    protected static function getCacheFilename(ServerRequestInterface $request)
    {
        $parts = pathinfo($request->getUri()->getPath());
        $path = '/'.(isset($parts['dirname']) ? $parts['dirname'] : '');
        $filename = isset($parts['basename']) ? $parts['basename'] : '';

        //if it's a directory, append "/index.html"
        if (empty($parts['extension'])) {
            if ($path === '/') {
                $path .= $filename;
            } else {
                $path .= '/'.$filename;
            }

            $filename = 'index.'.($request->getAttribute('FORMAT') ?: 'html');
        }

        return $path.'/'.$filename;
    }

    /**
     * Write the stream to the given path
     *
     * @param StreamInterface $stream
     * @param string          $path
     */
    protected static function writeStream(StreamInterface $stream, $path)
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $handle = fopen($path, 'wb+');

        if (false === $handle) {
            throw new RuntimeException('Unable to write to designated path');
        }

        $stream->rewind();

        while (!$stream->eof()) {
            fwrite($handle, $stream->read(4096));
        }

        fclose($handle);
    }

    /**
     * Parses and returns the cache-control header values
     *
     * @param string $header
     *
     * @return array
     */
    protected static function parseCacheControl($header)
    {
        $cache = [];

        foreach (array_map('trim', explode(',', strtolower($header))) as $part) {
            if (strpos($part, '=')) {
                $part = array_map('trim', explode($part, $part, 2));
                $cache[$part[0]] = $part[1];
            } else {
                $cache[$part] = true;
            }
        }

        return $cache;
    }
}
