<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Aura\Router\RouterContainer;
use Aura\Router\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuraRouter
{
    use Utils\CallableTrait;
    use Utils\AttributeTrait;

    const KEY = 'AURA_ROUTE';

    /**
     * @var RouterContainer The router container
     */
    private $router;

    /**
     * Returns the route instance.
     *
     * @param ServerRequestInterface $request
     *
     * @return Route|null
     */
    public static function getRoute(ServerRequestInterface $request)
    {
        return self::getAttribute($request, self::KEY);
    }

    /**
     * Set the RouterContainer instance.
     *
     * @param RouterContainer $router
     */
    public function __construct(RouterContainer $router)
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
        $matcher = $this->router->getMatcher();
        $route = $matcher->match($request);

        if (!$route) {
            $failedRoute = $matcher->getFailedRoute();

            switch ($failedRoute->failedRule) {
                case 'Aura\Router\Rule\Allows':
                    return $response->withStatus(405); // 405 METHOD NOT ALLOWED

                case 'Aura\Router\Rule\Accepts':
                    return $response->withStatus(406); // 406 NOT ACCEPTABLE

                default:
                    return $response->withStatus(404); // 404 NOT FOUND
            }
        }

        $request = self::setAttribute($request, self::KEY, $route);

        foreach ($route->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $response = $this->executeCallable($route->handler, $request, $response);

        return $next($request, $response);
    }
}
