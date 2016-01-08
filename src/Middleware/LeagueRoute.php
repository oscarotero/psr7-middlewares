<?php

namespace Psr7Middlewares\Middleware;

use League\Route\RouteCollection;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class LeagueRoute
{
    /**
     * @var RouteCollection The router container
     */
    private $router;

    /**
     * Set the RouteCollection instance.
     *
     * @param RouteCollection $router
     */
    public function __construct(RouteCollection $router)
    {
        $this->router = $router;
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
        return $next($request, $this->router->dispatch($request, $response));
    }
}
