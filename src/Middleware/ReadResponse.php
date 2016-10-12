<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to read the response.
 */
class ReadResponse
{
    use Utils\FileTrait;
    use Utils\StreamTrait;

    private $continueOnError = false;

    /**
     * Configure if continue to the next middleware if the response has not found.
     *
     * @param bool $continueOnError
     *
     * @return self
     */
    public function continueOnError($continueOnError = true)
    {
        $this->continueOnError = $continueOnError;

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
        //If the method is not allowed
        if ($request->getMethod() !== 'GET') {
            if ($this->continueOnError) {
                return $next($request, $response);
            }

            return $response->withStatus(405);
        }

        $file = $this->getFilename($request);

        //If the file does not exists, check if is gzipped
        if (!is_file($file)) {
            $file .= '.gz';

            if (EncodingNegotiator::getEncoding($request) !== 'gzip' || !is_file($file)) {
                if ($this->continueOnError) {
                    return $next($request, $response);
                }

                return $response->withStatus(404);
            }

            $response = $response->withHeader('Content-Encoding', 'gzip');
        }

        //Handle range header
        return $this->range($request, $response->withBody(self::createStream($file, 'r')));
    }

    /**
     * Handle range requests.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
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
