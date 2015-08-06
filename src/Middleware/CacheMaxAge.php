<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\CacheTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to cache the response using Cache-Control: max-age directive
 */
class CacheMaxAge
{
    use CacheTrait;

    protected $streamCreator;
    protected $directory = '';
    protected $basePath;

    /**
     * Constructor. Set the document root
     *
     * @param callable $streamCreator
     */
    public function __construct(callable $streamCreator)
    {
        $this->streamCreator = $streamCreator;
    }

    /**
     * Set the basepath used in the request
     *
     * @param string $basePath
     * 
     * @return self
     */
    public function basePath($basePath)
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Set the directory value
     *
     * @param string $path
     *
     * @return self
     */
    public function cacheDirectory($path)
    {
        $this->directory = $path;

        return $this;
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
        $stream_file = $this->directory.static::getCacheFilename($request, $this->basePath);
        $headers_file = "{$stream_file}.headers";

        if (is_file($stream_file) && is_file($headers_file)) {
            $headers = include $headers_file;

            if (isset($headers['Cache-Control'][0])) {
                $cache = static::parseCacheControl($headers['Cache-Control'][0]);

                if (isset($cache['max-age'])) {
                    $time = filemtime($stream_file) + $cache['max-age'];

                    if ($time > time()) {
                        $response = $response->withBody(call_user_func($this->streamCreator, $stream_file));

                        foreach ($headers as $name => $header) {
                            $response = $response->withHeader($name, $header);
                        }

                        return $response;
                    }
                }
            }
        }

        $response = $next($request, $response);

        if (static::isCacheable($request, $response)) {
            static::writeStream($response->getBody(), $stream_file);
            file_put_contents($headers_file, '<?php return '.var_export($response->getHeaders(), true).';');
        }

        return $response;
    }
}
