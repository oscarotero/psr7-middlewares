<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\{Utils, Middleware, Transformers};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use RuntimeException;

class Minify
{
    use Utils\CacheTrait;
    use Utils\ResolverTrait;

    /**
     * @var bool Minify only cacheable responses
     */
    private $forCache = false;

    /**
     * Set forCache directive.
     *
     * @param bool $forCache
     *
     * @return self
     */
    public function forCache(bool $forCache = true): self
    {
        $this->forCache = $forCache;

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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if ($this->forCache && !self::isCacheable($request, $response)) {
            return $next($request, $response);
        }

        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('Minify middleware needs FormatNegotiator executed before');
        }

        $resolver = $this->resolver ?: new Transformers\Minifier();
        $transformer = $resolver->resolve(FormatNegotiator::getFormat($request));

        if ($transformer) {
            $response = $transformer($response);
        }

        return $next($request, $response);
    }
}
