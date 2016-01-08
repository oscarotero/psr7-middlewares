<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Transformers;
use Psr7Middlewares\Utils;
use ParagonIE\CSPBuilder\CSPBuilder;

/**
 * Middleware to add the Content-Security-Policy header to the responses
 */
class Csp
{
    /**
     * @var CSPBuilder
     */
    private $builder;

    /**
     * Set CSPBuilder
     * 
     * @param CSPBuilder $builder
     */
    public function __construct(CSPBuilder $builder)
    {
        $this->builder = $builder;

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
        $response = $this->builder->injectCSPHeader($response);

        return $next($request, $response);
    }
}
