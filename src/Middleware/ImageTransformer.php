<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Imagecow\Image;
use RuntimeException;

/**
 * Middleware to manipulate images on demand.
 */
class ImageTransformer
{
    /**
     * @var array Enable client hints
     */
    protected $clientHints = false;

    /**
     * @var array Available sizes
     */
    protected $sizes = [];

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
     * Enable the client hints.
     * 
     * @param bool $clientHints
     * 
     * @return self
     */
    public function clientHints($clientHints = true)
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

                if ($this->clientHints) {
                    return $response->withHeader('Accept-CH', 'DPR,Width,Viewport-Width');
                }

                return $response;

            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'png':
                $info = $this->parsePath($request->getUri()->getPath());

                if (!$info) {
                    break;
                }

                //Removes the transform from the path
                list($path, $transform) = $info;
                $request = $request->withUri($request->getUri()->withPath($path));

                $response = $next($request, $response);

                //Transform
                if ($response->getStatusCode() === 200 && $response->getBody()->getSize()) {
                    return $this->transform($request, $response, $transform);
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

        if ($this->clientHints) {
            $hints = [];

            foreach (['Dpr', 'Viewport-Width', 'Width'] as $name) {
                if ($request->hasHeader($name)) {
                    $hints[$name] = $request->getHeaderLine($name);
                }
            }

            $image->setClientHints($hints);

            if ($hints) {
                $response = $response->withHeader('Vary', implode(', ', $hints));
            }
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
}
