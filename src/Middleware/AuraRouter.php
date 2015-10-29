<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Aura\Router\RouterContainer;
use Aura\Router\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class AuraRouter
{
    use Utils\RouterTrait;
    use Utils\ArgumentsTrait;

    const KEY = 'AURA_ROUTE';

    /**
     * @var RouterContainer|null The router container
     */
    protected $router;

    /**
     * Returns the route instance.
     *
     * @param ServerRequestInterface $request
     *
     * @return Route|null
     */
    public static function getRoute(ServerRequestInterface $request)
    {
        return Middleware::getAttribute($request, self::KEY);
    }

    /**
     * Constructor.Set the RouterContainer instance.
     *
     * @param RouterContainer $router
     */
    public function __construct(RouterContainer $router = null)
    {
        if ($router !== null) {
            $this->router($router);
        }
    }

    /**
     * Extra arguments passed to the controller.
     *
     * @param RouterContainer $router
     *
     * @return self
     */
    public function router(RouterContainer $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($this->router === null) {
            throw new RuntimeException('No RouterContainer instance has been provided');
        }

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

        $request = Middleware::setAttribute($request, self::KEY, $route);

        foreach ($route->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $response = self::executeTarget($route->handler, $this->arguments, $request, $response);

        return $next($request, $response);
    }
}
