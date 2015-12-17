<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Imagecow\Image;
use RuntimeException;

/**
 * Middleware to manipulate images on demand and generate responsiveness
 */
class ImageTransformer
{
    use Utils\BasePathTrait;
    use Utils\StorageTrait;
    use Utils\ContinueTrait;

    protected $sizes;

    /**
     * Define the available sizes, for example:
     * [
     *    'small'  => 'resizeCrop,50,50',
     *    'medium' => 'resize,500',
     *    'big'    => 'resize,1000',
     * ]
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
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
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

            if (!is_file($file)) {
                return $this->continue ? $next($request, $response) : $response->withStatus(404);
            }

            return $this->transform($file, $transform, $response);
        }

        return $next($request, $response);
    }

    /**
     * Transform the image
     * 
     * @param string            $file
     * @param string            $transform
     * @param ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    private function transform($file, $transform, ResponseInterface $response)
    {
        $image = Image::create($file);
        $image->transform($transform);

        $body = Middleware::createStream();
        $body->write($image->getString());

        return $response
            ->withBody($body)
            ->withHeader('Content-Type', $image->getMimeType());
    }

    /**
     * Parses the path and return the file and transform values
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

            //list of available sizes
            if (is_array($this->sizes)) {
                if (!isset($this->sizes[$transform])) {
                    return;
                }

                $transform = $this->sizes[$transform];
            }

            return [Utils\Path::join($this->storage, $info['dirname'], "{$file}.".$info['extension']), $transform];
        }
    }
}
