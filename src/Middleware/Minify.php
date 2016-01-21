<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Transformers;
use RuntimeException;

class Minify
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
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
