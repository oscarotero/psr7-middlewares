<?php declare(strict_types=1);

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\{Middleware, Utils, Transformers};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use RuntimeException;

/**
 * Middleware to gzip encode the response body.
 */
class Gzip
{
    use Utils\ResolverTrait;

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if (!Middleware::hasAttribute($request, EncodingNegotiator::KEY)) {
            throw new RuntimeException('Gzip middleware needs EncodingNegotiator executed before');
        }

        $resolver = $this->resolver ?: new Transformers\Encoder();
        $transformer = $resolver->resolve(EncodingNegotiator::getEncoding($request));

        if ($transformer) {
            $response = $transformer($response);
        }

        return $next($request, $response);
    }
}
