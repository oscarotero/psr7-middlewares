<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Middleware to save the response into a file.
 */
class SaveResponse
{
    use Utils\FileTrait;

    /**
     * Execute the middleware.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response = $next($request, $response);

        if ($this->canSave($request, $response)) {
            $path = $this->getFilename($request);

            //if it's gz compressed, append .gz
            if (strtolower($response->getHeaderLine('Content-Encoding')) === 'gzip') {
                $path .= '.gz';
            }

            self::writeStream($response->getBody(), $path);
        }

        return $response;
    }

    /**
     * Check whether the response can be saved or not.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return bool
     */
    private function canSave(RequestInterface $request, ResponseInterface $response)
    {
        if ($request->getMethod() !== 'GET') {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if (!$this->appendQuery && !empty($request->getUri()->getQuery())) {
            return false;
        }

        if ($response->hasHeader('location')) {
            return false;
        }

        $cacheControl = $response->getHeaderLine('Cache-Control');

        if ($cacheControl && (stripos($cacheControl, 'no-cache') !== false || stripos($cacheControl, 'no-store') !== false)) {
            return false;
        }

        return true;
    }

    /**
     * Write the stream to the given path.
     *
     * @param StreamInterface $stream
     * @param string          $path
     */
    private static function writeStream(StreamInterface $stream, $path)
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
}
