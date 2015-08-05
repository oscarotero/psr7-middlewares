<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\CacheTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to cache the response
 */
class Cache
{
    use CacheTrait;

    protected $streamCreator;
    protected $maxAge = 3600;
    protected $directory = '';

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
     * Set the max-age value
     *
     * @param int $maxAge
     *
     * @return self
     */
    public function maxAge($maxAge)
    {
        $this->maxAge = (int) $maxAge;

        return $this;
    }

    /**
     * Set the directory value
     *
     * @param string $path
     *
     * @return self
     */
    public function directory($path)
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
        $stream = $this->directory.static::getCacheFilename($request);
        $headers = "{$stream}.headers";

        if (is_file($stream) && is_file($headers)) {
            $headers = json_decode(file_get_contents($headers));

            if (isset($headers['Cache-Control'][0])) {
                $cache = static::parseCacheControl($headers['Cache-Control'][0]);
                $time = filemtime($stream);

                if (isset($cache['max-age'])) {
                    $time += $cache['max-age'];
                } else {
                    $time += $this->maxAge;
                }

                if ($time > time()) {
                    $response = $response->withBody(call_user_func($this->streamCreator, $stream));

                    foreach ($headers as $name => $header) {
                        $response = $response->withHeader($name, $header);
                    }

                    return $response;
                }
            }
        }

        $response = $next($request, $response);

        if (static::isCacheable($request, $response)) {
            static::writeFile($response->getBody(), $stream);
            file_put_contents($headers, json_encode($response->getHeaders()));
        }

        return $response;
    }
}
