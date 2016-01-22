<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Cache\CacheItemPoolInterface;
use Imagecow\Image;
use RuntimeException;

/**
 * Middleware to manipulate images on demand.
 */
class ImageTransformer
{
    use Utils\CacheMessageTrait;

    /**
     * @var array|false Enable client hints
     */
    private $clientHints = false;

    /**
     * @var array Available sizes
     */
    private $sizes = [];

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
        $this->sizes = $sizes;
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
        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('ResponsiveImage middleware needs FormatNegotiator executed before');
        }

        switch (FormatNegotiator::getFormat($request)) {
            case 'html':
                $response = $next($request, $response);

                if (!empty($this->clientHints)) {
                    return $response->withHeader('Accept-CH', implode(',', $this->clientHints));
                }

                return $response;

            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'png':
                $key = $this->getCacheKey($request);

                //Get from the cache
                if ($cached = $this->getFromCache($key, $response)) {
                    return $cached;
                }

                $uri = $request->getUri();
                $info = $this->parsePath($uri->getPath());

                if (!$info) {
                    break;
                }

                //Removes the transform from the path
                list($path, $transform) = $info;
                $request = $request->withUri($uri->withPath($path));

                $response = $next($request, $response);

                //Transform
                if ($response->getStatusCode() === 200 && $response->getBody()->getSize()) {
                    $response = $this->transform($request, $response, $transform);

                    //Save in the cache
                    $this->saveIntoCache($key, $response);
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

        $body = Middleware::createStream();
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
        $file = $info['basename'];
        $path = $info['dirname'];

        foreach ($this->sizes as $pattern => $transform) {
            if (strpos($pattern, '/') === false) {
                $patternFile = $pattern;
                $patternPath = '';
            } else {
                $patternFile = pathinfo($pattern, PATHINFO_BASENAME);
                $patternPath = pathinfo($pattern, PATHINFO_BASENAME);
            }

            if (substr($file, 0, strlen($patternFile)) === $patternFile && ($patternPath === '' || substr($path, -strlen($patternPath)) === $patternPath)) {
                return [Utils\Helpers::joinPath($path, substr($file, strlen($patternFile))), $transform];
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
