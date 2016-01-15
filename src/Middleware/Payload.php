<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\{Middleware, Utils, Transformers};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};

/**
 * Middleware to parse the body.
 */
class Payload
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
        if (!$request->getParsedBody() && in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'], true)) {
            $resolver = $this->resolver ?: new Transformers\BodyParser();
            $transformer = $resolver->resolve(trim($request->getHeaderLine('Content-Type')));

            if ($transformer) {
                $request = $transformer($request);
            }
        }

        return $next($request, $response);
    }
}
