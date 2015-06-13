<?php
namespace Psr7Middlewares;

use RuntimeException;
use Aura\Router\RouterContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuraRouter
{
	protected $router;

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

        if ($route) {
            $response = call_user_func($route->handler, $request, $response);
        }

        return $next($request, $response);
	}
}
