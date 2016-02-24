<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Negotiation\EncodingNegotiator as Negotiator;

/**
 * Middleware that returns the client preferred encoding.
 */
class EncodingNegotiator
{
    use Utils\NegotiateTrait;
    use Utils\AttributeTrait;

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
        return self::getAttribute($request, self::KEY);
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
        $encoding = $this->negotiateHeader($request->getHeaderLine('Accept-Encoding'), new Negotiator(), $this->encodings);

        $request = self::setAttribute($request, self::KEY, $encoding);

        return $next($request, $response);
    }
}
