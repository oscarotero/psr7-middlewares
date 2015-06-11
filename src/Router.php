<?php
namespace Fol\HttpMiddlewares;

use Aura\Router\RouterContainer;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Router
{
	protected $router;

	public function __construct()
	{
		$this->router = new RouterContainer();
	}

	public function getRouter()
	{
		return $this->router;
	}

	public function attach($namePrefix, $pathPrefix, $callback)
	{
		$this->router->getMap()->attach($namePrefix, $pathPrefix, $callback);
	}

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
	{
		$matcher = $this->router->getMatcher();
        $route = $matcher->match($request);

        if ($route) {
            $response = call_user_func($route->handler, $request, $response);
        }

        return $next($request, $response);
	}
}
