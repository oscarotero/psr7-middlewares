<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
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

        //If the file does not exists, check if is gzipped
        if (!is_file($file)) {
            $file .= '.gz';

            if (EncodingNegotiator::getEncoding($request) !== 'gzip' || !is_file($file)) {
                return $response->withStatus(404);
            }

            $response = $response->withHeader('Content-Encoding', 'gzip');
        }

        $body = Middleware::createStream();

        $stream = fopen($file, 'r');

        while (!feof($stream)) {
            $body->write(fread($stream, 1024 * 8));
        }
        fclose($stream);

        $response = $response->withBody($body);

        //Handle range header
        $response = $this->range($request, $response);

        return $next($request, $response);
    }

    private static function range(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response = $response->withHeader('Accept-Ranges', 'bytes');

        $range = $request->getHeaderLine('Range');

        if (empty($range) || !($range = self::parseRangeHeader($range))) {
            return $response;
        }

        list($first, $last) = $range;
        $size = $response->getBody()->getSize();

        if ($last === null) {
            $last = $size - 1;
        }

        return $response
            ->withStatus(206)
            ->withHeader('Content-Length', (string) ($last - $first + 1))
            ->withHeader('Content-Range', sprintf('bytes %d-%d/%d', $first, $last, $size));
    }

    /**
     * Parses a range header, for example: bytes=500-999.
     *
     * @param string $header
     *
     * @return false|array [first, last]
     */
    private static function parseRangeHeader($header)
    {
        if (preg_match('/bytes=(?P<first>\d+)-(?P<last>\d+)?/', $header, $matches)) {
            return [
                (int) $matches['first'],
                isset($matches['last']) ? (int) $matches['last'] : null,
            ];
        }

        return false;
    }
}
