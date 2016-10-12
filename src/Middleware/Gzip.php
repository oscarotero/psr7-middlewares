<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Transformers;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Middleware to gzip encode the response body.
 */
class Gzip
{
    use Utils\ResolverTrait;
    use Utils\AttributeTrait;
    use Utils\StreamTrait;

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
        if (!self::hasAttribute($request, EncodingNegotiator::KEY)) {
            throw new RuntimeException('Gzip middleware needs EncodingNegotiator executed before');
        }

        $response = $next($request, $response);

        $resolver = $this->resolver ?: new Transformers\Encoder();
        $encoding = EncodingNegotiator::getEncoding($request);
        $transformer = $resolver->resolve($encoding);

        if ($transformer) {
            $body = $response->getBody();

            return $response
                ->withHeader('Content-Encoding', $encoding)
                ->withBody($transformer($body, self::createStream($body)));
        }

        return $response;
    }
}
