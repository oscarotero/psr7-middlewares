<?php
namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr7Middlewares\Middleware;

/**
 * Provides ability to route Psr7Middlewares specific attributes into scalar attributes.
 *
 * @todo Raise an exception if no attribute found?
 */
class AttributeRouter
{
    /**
     * @var array
     */
    private $routing = [];

    /**
     * Example:
     *
     * [
     *      BasicAuthentication::KEY => 'basic.username'
     * ]
     *
     * @param array $routing
     */
    public function __construct($routing)
    {
        $this->routing = $routing;
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
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ) {
        foreach ($this->routing as $middleware => $attribute) {
            $request = $request->withAttribute(
                $attribute,
                Middleware::getAttribute($request, $middleware)
            );
        }

        return $next($request, $response);
    }
}