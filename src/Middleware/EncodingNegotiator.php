<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Negotiation\EncodingNegotiator as Negotiator;

/**
 * Middleware that returns the client preferred encoding.
 */
class EncodingNegotiator
{
    const KEY = 'ENCODING';

    /**
     * @var array Available encodings
     */
    private $encodings = [
        'gzip',
        'deflate',
    ];

    /**
     * Returns the encoding.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    public static function getEncoding(ServerRequestInterface $request)
    {
        return Middleware::getAttribute($request, self::KEY);
    }

    /**
     * Constructor. Defines de available encodings.
     *
     * @param array $encodings
     */
    public function __construct(array $encodings = null)
    {
        if ($encodings !== null) {
            $this->encodings($encodings);
        }
    }

    /**
     * Configure the available encodings.
     *
     * @param array $encodings
     *
     * @return self
     */
    public function encodings(array $encodings)
    {
        $this->encodings = $encodings;

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
        $request = Middleware::setAttribute($request, self::KEY, $this->getFromHeader($request));

        return $next($request, $response);
    }

    /**
     * Returns the encoding format using the Accept-Encoding header.
     *
     * @return null|string
     */
    private function getFromHeader(ServerRequestInterface $request)
    {
        $accept = $request->getHeaderLine('Accept-Encoding');

        if (empty($accept)) {
            return;
        }

        $encoding = (new Negotiator())->getBest($accept, $this->encodings);

        if ($encoding) {
            return $encoding->getValue();
        }
    }
}
