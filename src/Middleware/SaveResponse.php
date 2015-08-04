<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\CacheTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Middleware to save the response into a file
 */
class SaveResponse
{
    use CacheTrait;

    protected $documentRoot;

    /**
     * Creates an instance of this middleware
     *
     * @param string $documentRoot
     *
     * @return SaveResponse
     */
    public static function create($documentRoot = '')
    {
        return new static($documentRoot);
    }

    /**
     * Constructor. Set the document root
     *
     * @param string $documentRoot
     */
    public function __construct($documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }

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
        if (!static::isCacheable($request, $response)) {
            return $next($request, $response);
        }

        $this->writeFile($response->getBody(), $this->documentRoot.static::getCacheFilename($request));

        return $next($request, $response);
    }

    /**
     * Write the stream to given path
     *
     * @param StreamInterface $stream
     * @param string          $path
     */
    private function writeFile(StreamInterface $stream, $path)
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
