<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to read the response.
 */
class ReadResponse
{
    use Utils\FileTrait;

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
        //If basePath does not match
        if (!$this->testBasePath($request->getUri()->getPath())) {
            return $next($request, $response);
        }

        //If the method is not allowed
        if ($request->getMethod() !== 'GET') {
            return $response->withStatus(405);
        }

        $file = $this->getFilename($request);

        //If the file does not exists
        if (!is_file($file)) {
            return $response->withStatus(404);
        }

        $response = $response->withBody(Middleware::createStream($file));

        //Handle range
        $response = $this->range($request, $response);
        
        return $next($request, $response);
    }

    private static function range(RequestInterface $request, ResponseInterface $response)
    {
        $response = $response->withHeader('Accept-Ranges', 'bytes');

        $range = $request->getHeaderLine('Range');

        if (empty($range) || !($range = self::parseRangeHeader($range))) {
            return $response;
        }

        list($unit, $first, $last) = $range;
        $size = $response->getBody()->getSize();

        if (!$last) {
            $last = $size - 1;
        }

        return $response
            ->withStatus(206)
            ->withHeader('Content-Length', $last - $first + 1)
            ->withHeader('Content-Range', sprintf('%s %d-%d/%d', $unit, $first, $last,  $size));
    }

    /**
     * Parses a range header, for example: bytes=500-999.
     *
     * @param string $header
     *
     * @return false|array [unit, first, last]
     */
    private static function parseRangeHeader($header)
    {
        if (preg_match('/(?P<unit>[\w]+)=(?P<first>\d+)-(?P<last>\d+)?/', $header, $matches)) {
            return [
                $matches['unit'],
                (int) $matches['first'],
                isset($matches['last']) ? (int) $matches['last'] : null,
            ];
        }

        return false;
    }
}
