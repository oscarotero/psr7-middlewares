<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use League\Route\RouteCollection;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class LeagueRoute
{
    use Utils\ResolverTrait;

    /**
     * @var RouteCollection|null The router container
     */
    protected $router;

    /**
     * Constructor. Set the RouteCollection instance.
     *
     * @param RouteCollection $router
     */
    public function __construct(RouteCollection $router = null)
    {
        if ($router !== null) {
            $this->router($router);
        }
    }

    /**
     * Extra arguments passed to the controller.
     *
     * @param RouteCollection $router
     *
     * @return self
     */
    public function router(RouteCollection $router)
    {
        $this->router = $router;

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
        $router = $this->router ?: $this->getFromResolver(RouteCollection::CLASS);

        if (empty($router)) {
            throw new RuntimeException('No RouteCollection instance has been provided');
        }

        return $next($request, $router->dispatch($request, $response));
    }
}
