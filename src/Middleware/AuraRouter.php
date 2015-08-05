<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\RouterTrait;
use Aura\Router\RouterContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class AuraRouter
{
    use RouterTrait;

    protected $router;
    protected $arguments = [];

    /**
     * Constructor
     * You can specify the RouterContainer instance or a callable to fetch it in lazy mode
     *
     * @param RouterContainer|callable $router
     */
    public function __construct($router)
    {
        $this->router = $router;
    }

    /**
     * Extra arguments passed to the controller
     * 
     * @return self
     */
    public function arguments()
    {
        $this->arguments = func_get_args();

        return $this;
    }

    /**
     * Execute the middleware
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $router = $this->getRouter();
        $matcher = $router->getMatcher();
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

        $request = $request->withAttribute('ROUTE', $route);

        foreach ($route->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $response = self::executeTarget($route->handler, $this->arguments, $request, $response);

        return $next($request, $response);
    }

    /**
     * Returns the route
     *
     * @throws RuntimeException If the route cannot be fetched
     * @return RouterContainer
     */
    protected function getRouter()
    {
        if (is_callable($this->router)) {
            $this->router = call_user_func($this->router);
        }

        if ($this->router instanceof RouterContainer) {
            return $this->router;
        }

        throw new RuntimeException('No RouterContainer instance has been provided');
    }
}
