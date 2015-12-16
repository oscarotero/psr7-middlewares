<?php

namespace Psr7Middlewares\Middleware;

use League\Route\RouteCollection;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class LeagueRoute
{
    /**
     * @var RouteCollection|null The router container
     */
    private $router;

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
        if (empty($this->router)) {
            throw new RuntimeException('No RouteCollection instance has been provided');
        }

        return $next($request, $this->router->dispatch($request, $response));
    }
}
