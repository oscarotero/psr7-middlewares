<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr7Middlewares\Utils;

/**
 * Provides ability to route Psr7Middlewares specific attributes into scalar attributes.
 */
class AttributeMapper
{
    use Utils\AttributeTrait;

    /**
     * @var array
     */
    private $mapping = [];

    /**
     * Example:.
     *
     * [
     *      BasicAuthentication::KEY => 'basic.username'
     * ]
     *
     * @param array $mapping
     */
    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
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
        foreach ($this->mapping as $middleware => $attribute) {
            $request = $request->withAttribute(
                $attribute,
                self::getAttribute($request, $middleware)
            );
        }

        return $next($request, $response);
    }
}
