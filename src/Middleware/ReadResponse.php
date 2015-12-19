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

        //Handle range
        $response = $response
            ->withBody(Middleware::createStream($file))
            ->withHeader('Accept-Ranges', 'bytes');

        $range = $request->getHeaderLine('Range');

        if (!empty($range) && ($range = self::parseRangeHeader($range))) {
            $length = $response->getBody()->getSize();

            $response = $response->withHeader('Content-Range', sprintf('%s %d-%d/%d', $range[0], $range[1], $range[2] ?: $length,  $length));
        }

        return $next($request, $response->withBody(Middleware::createStream($file)));
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
