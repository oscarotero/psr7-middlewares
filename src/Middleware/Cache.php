<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware as Factory;
use Psr7Middlewares\Utils\CacheTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to cache the response using Cache-Control and other directives
 */
class Cache
{
    use CacheTrait;

    protected $directory = '';

    /**
     * Constructor. Set the cache directory
     *
     * @param string   $cacheDirectory
     */
    public function __construct($cacheDirectory)
    {
        $this->directory = $path;
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
        list($headers_file, $stream_file) = $this->getCacheFilename($request);

        if (is_file($stream_file) && is_file($headers_file)) {
            $headers = include $headers_file;

            if (static::cacheIsFresh($headers, $stream_file)) {
                $response = $response->withBody(Factory::createStream($stream_file));

                foreach ($headers as $name => $header) {
                    $response = $response->withHeader($name, $header);
                }

                return $response;
            }
        }

        $response = $next($request, $response);

        if (static::isCacheable($request, $response)) {
            static::writeStream($response->getBody(), $stream_file);
            file_put_contents($headers_file, '<?php return '.var_export($response->getHeaders(), true).';');
        }

        return $response;
    }

    /**
     * Check the max-age directive
     * 
     * @param array  $cacheHeaders
     * @param string $cacheBodyFile
     * 
     * @return boolean
     */
    protected static function cacheIsFresh(array $cacheHeaders, $cacheBodyFile)
    {
        $cacheTime = filemtime($cacheBodyFile);
        $now = new \Datetime();

        //Cache-Control
        if (isset($cacheHeaders['Cache-Control'][0])) {
            $cacheControl = static::parseCacheControl($cacheHeaders['Cache-Control'][0]);
        
            //Max age
            if (isset($cacheControl['max-age']) && ($cacheTime + $cacheControl['max-age'] < $now->get('U'))) {
                return false;
            }
        }

        //Expires
        if (isset($cacheHeaders['Expires'][0])) {
            $expires = new \Datetime($cacheHeaders['Expires'][0]);

            if ($expires < $now) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the filename of the response cache file
     *
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    protected function getCacheFilename(ServerRequestInterface $request)
    {
        $file = $this->cacheDirectory.'/'.md5((string) $request->getUri());

        return ["{$file}.headers", "{$file}.body"];
    }
}
