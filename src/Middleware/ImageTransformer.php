<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Imagecow\Image;
use RuntimeException;
use Exception;

/**
 * Middleware to manipulate images on demand.
 */
class ImageTransformer
{
    use Utils\BasePathTrait;

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

        //If it's not an image or basePath does not match or invalid transform values, don't do anything
        if (!in_array(FormatNegotiator::getFormat($request), ['jpg', 'jpeg', 'gif', 'png']) || !$this->testBasePath($request->getUri()->getPath()) || !($info = $this->parsePath($request->getUri()->getPath()))) {
            return $next($request, $response);
        }

        list($path, $transform) = $info;
        $uri = $request->getUri()->withPath($path);
        $request = $request->withUri($uri);

        $response = $next($request, $response);

        //Check the response and transform the image
        if ($transform && $response->getStatusCode() === 200 && $response->getBody()->getSize()) {
            return $this->transform($response, $transform);
        }

        return $response;
    }

    /**
     * Transform the image.
     * 
     * @param ResponseInterface $response
     * @param string            $transform
     * 
     * @return ResponseInterface
     */
    private function transform(ResponseInterface $response, $transform)
    {
        $image = Image::createFromString((string) $response->getBody());
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
     * @return null|array [file, transform]
     */
    private function parsePath($path)
    {
        $info = pathinfo($path);

        try {
            $pieces = explode('.', $info['filename'], 2);
        } catch (Exception $e) {
            return;
        }

        if (count($pieces) === 2) {
            list($transform, $file) = $pieces;

            //Check if the size is valid
            if (!isset($this->sizes[$transform])) {
                return;
            }

            return [Utils\Helpers::joinPath($info['dirname'], "{$file}.".$info['extension']), $this->sizes[$transform]];
        }
    }
}
