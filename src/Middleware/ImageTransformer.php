<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Cache\CacheItemPoolInterface;
use Imagecow\Image;

/**
 * Middleware to manipulate images on demand.
 */
class ImageTransformer
{
    use Utils\CacheMessageTrait;
    use Utils\AttributeTrait;
    use Utils\StreamTrait;

    const KEY_GENERATOR = 'IMAGE_TRANSFORMER';

    /**
     * @var array|false Enable client hints
     */
    private $clientHints = false;

    /**
     * @var array Available sizes
     */
    private $sizes = [];

    /**
     * Returns a callable to generate urls.
     *
     * @param ServerRequestInterface $request
     *
     * @return callable|null
     */
    public static function getGenerator(ServerRequestInterface $request)
    {
        return self::getAttribute($request, self::KEY_GENERATOR);
    }

    /**
     * Define the available sizes, for example:
     * [
     *    'small'  => 'resizeCrop,50,50',
     *    'medium' => 'resize,500',
     *    'big'    => 'resize,1000',
     * ].
     *
     * @param array $sizes
     */
    public function __construct(array $sizes)
    {
        foreach ($sizes as $prefix => $transform) {
            if (strpos($prefix, '/') === false) {
                $path = '';
            } else {
                $path = pathinfo($prefix, PATHINFO_DIRNAME);
                $prefix = pathinfo($prefix, PATHINFO_BASENAME);
            }

            if (!isset($this->sizes[$prefix])) {
                $this->sizes[$prefix] = [$path => $transform];
            } else {
                $this->sizes[$prefix][$path] = $transform;
            }
        }
    }

    /**
     * To save the transformed images in the cache.
     *
     * @param CacheItemPoolInterface $cache
     *
     * @return self
     */
    public function cache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Enable the client hints.
     *
     * @param array $clientHints
     *
     * @return self
     */
    public function clientHints($clientHints = ['Dpr', 'Viewport-Width', 'Width'])
    {
        $this->clientHints = $clientHints;

        return $this;
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        switch (Utils\Helpers::getMimeType($response)) {
            case 'image/jpeg':
            case 'image/gif':
            case 'image/png':
                $key = $this->getCacheKey($request);

                //Get from the cache
                if ($cached = $this->getFromCache($key, $response)) {
                    return $cached;
                }

                $info = $this->parsePath($request->getUri()->getPath());

                if (!$info) {
                    break;
                }

                //Removes the transform info in the path
                list($path, $transform) = $info;
                $request = $request->withUri($request->getUri()->withPath($path));
                $response = $next($request, $response);

                //Transform
                if ($response->getStatusCode() === 200 && $response->getBody()->getSize()) {
                    $response = $this->transform($request, $response, $transform);

                    //Save in the cache
                    $this->saveIntoCache($key, $response);
                }

                return $response;

            case 'text/html':
                $generator = function ($path, $transform) {
                    $info = pathinfo($path);

                    if (!isset($this->sizes[$transform])) {
                        throw new \InvalidArgumentException(sprintf('The image size "%s" is not valid', $transform));
                    }

                    return Utils\Helpers::joinPath($info['dirname'], $transform.$info['basename']);
                };

                $request = self::setAttribute($request, self::KEY_GENERATOR, $generator);
                $response = $next($request, $response);

                if (!empty($this->clientHints)) {
                    return $response->withHeader('Accept-CH', implode(',', $this->clientHints));
                }

                return $response;
        }

        return $next($request, $response);
    }

    /**
     * Transform the image.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param string                 $transform
     *
     * @return ResponseInterface
     */
    private function transform(ServerRequestInterface $request, ResponseInterface $response, $transform)
    {
        $image = Image::fromString((string) $response->getBody());
        $hints = $this->getClientHints($request);

        if ($hints) {
            $image->setClientHints($hints);
            $response = $response->withHeader('Vary', implode(', ', $hints));
        }

        $image->transform($transform);

        $body = self::createStream($response->getBody());
        $body->write($image->getString());

        return $response
            ->withBody($body)
            ->withHeader('Content-Type', $image->getMimeType());
    }

    /**
     * Parses the path and return the file and transform values.
     * For example, the path "/images/small.avatar.jpg" returns:
     * ["/images/avatar.jpg", "resizeCrop,50,50"].
     *
     * @param string $path
     *
     * @return false|array [file, transform]
     */
    private function parsePath($path)
    {
        $info = pathinfo($path);
        $basename = $info['basename'];
        $dirname = $info['dirname'];

        foreach ($this->sizes as $prefix => $paths) {
            if (strpos($basename, $prefix) !== 0) {
                continue;
            }

            foreach ($paths as $path => $transform) {
                $needle = $path === '' ? '' : substr($dirname, -strlen($path));

                if ($path === $needle) {
                    return [Utils\Helpers::joinPath($dirname, substr($basename, strlen($prefix))), $transform];
                }
            }
        }

        return false;
    }

    /**
     * Returns the client hints sent.
     *
     * @param ServerRequestInterface $request
     *
     * @return array|null
     */
    private function getClientHints(ServerRequestInterface $request)
    {
        if (!empty($this->clientHints)) {
            $hints = [];

            foreach ($this->clientHints as $name) {
                if ($request->hasHeader($name)) {
                    $hints[$name] = $request->getHeaderLine($name);
                }
            }

            return $hints;
        }
    }

    /**
     * Generates the key used to save the image in cache.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function getCacheKey(ServerRequestInterface $request)
    {
        $id = base64_encode((string) $request->getUri());
        $hints = $this->getClientHints($request);

        if ($hints) {
            $id .= '.'.base64_encode(json_encode($hints));
        }

        return $id;
    }
}
