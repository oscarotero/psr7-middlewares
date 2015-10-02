<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\CacheTrait;
use Psr7Middlewares\Utils\FileTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Middleware to save the response into a file
 */
class SaveResponse
{
    use CacheTrait;
    use FileTrait;

    /**
     * Execute the middleware
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response = $next($request, $response);

        if (!count($request->getQueryParams()) && static::isCacheable($request, $response)) {
            static::writeStream($response->getBody(), $this->getFilename($request));
        }

        return $response;
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
}
