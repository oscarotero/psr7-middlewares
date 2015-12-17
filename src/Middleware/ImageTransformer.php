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
    use Utils\BasePathTrait;
    use Utils\StorageTrait;

    protected $sizes;

    /**
     * Define the available sizes, for example:
     * [
     *    'small'  => 'resizeCrop,50,50',
     *    'medium' => 'resize,500',
     *    'big'    => 'resize,1000',
     * ].
     * 
     * @param array $sizes
     * 
     * @return self
     */
    public function sizes(array $sizes)
    {
        $this->sizes = $sizes;
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

        //Is not a valid image?
        if (!in_array(FormatNegotiator::getFormat($request), ['jpg', 'jpeg', 'gif', 'png'])) {
            return $next($request, $response);
        }

        //Get the file
        $info = $this->parsePath($request->getUri()->getPath());

        if ($info) {
            list($file, $transform) = $info;

            return $this->transform($file, $transform, $response);
        }

        return $next($request, $response);
    }

    /**
     * Transform the image.
     * 
     * @param string            $file
     * @param string            $transform
     * @param ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    private function transform($file, $transform, ResponseInterface $response)
    {
        //Check if the file exists
        if (!is_file($file)) {
            return $response->withStatus(404);
        }

        //Check if the size is valid
        if (is_array($this->sizes)) {
            if (!isset($this->sizes[$transform])) {
                return $response->withStatus(404);
            }

            $transform = $this->sizes[$transform];
        }

        $image = Image::create($file);
        $image->transform($transform);

        $body = Middleware::createStream();
        $body->write($image->getString());

        return $response
            ->withBody($body)
            ->withHeader('Content-Type', $image->getMimeType());
    }

    /**
     * Parses the path and return the file and transform values.
     * 
     * @param string $path
     * 
     * @return null|array [file, transform]
     */
    private function parsePath($path)
    {
        $path = $this->getBasePath($path);
        $info = pathinfo($path);
        $pieces = explode('.', $info['filename'], 2);

        if (count($pieces) === 2) {
            list($transform, $file) = $pieces;

            return [Utils\Path::join($this->storage, $info['dirname'], "{$file}.".$info['extension']), $transform];
        }
    }
}
