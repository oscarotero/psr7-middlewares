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
     * @var array 
     */
    private $rules = [];

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
     * @throws InvalidArgumentException if the rules array does not consist of a 
     * key/callable pair of values
     */
    public function __construct(RouterContainer $router, array $rules = null)
    {
        $this->router = $router;

        $this->addRule('Aura\Router\Rule\Allows', function($request, $response) {
            return $response->withStatus(405); // 405 METHOD NOT ALLOWED
        });

        $this->addRule('Aura\Router\Rule\Accepts', function($request, $response) {
            return $response->withStatus(406); // 406 NOT ACCEPTABLE
        });
        
        if ($rules !== null) {
            foreach ($rules as $name => $callback) {
                if (!is_string($name) || !is_callable($callback)) {
                    $message = 'Invalid rule given. Expected a valid key/callable '
                        . 'pair value';
                    throw \InvalidArgumentException($message);
                }
                
                $this->addRule($name, $callback);
            }
        }
    }
    
    /**
     * Adds a new rule
     */
    public function addRule(string $name, callable $callback)
    {
        $this->rules[$name] = $callback;
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
        $matcher = $this->router->getMatcher();
        $route = $matcher->match($request);

        if (!$route) {
            $failedRoute = $matcher->getFailedRoute();
            
            foreach($this->rules as $name => $callback) {
                if ($failedRoute->failedRule === $name) {
                    return $callback($request, $response);
                }
            }
            
            return $response->withStatus(404); // 404 NOT FOUND
        }

        $request = self::setAttribute($request, self::KEY, $route);

        foreach ($route->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $response = $this->executeCallable($route->handler, $request, $response);

        return $next($request, $response);
    }
}
