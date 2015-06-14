<?php
namespace Psr7Middlewares;

use RuntimeException;
use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class FastRoute
{
    protected $dispatcher;

    /**
     * Constructor
     * You can specify the Dispatcher instance or a callable to fetch it in lazy mode
     *
     * @param Dispatcher|callable $router
     */
    public function __construct($dispatcher)
    {
        $this->dispatcher = $dispatcher;
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
        $dispatcher = $this->getDispatcher();
        $route = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

        if ($route[0] === Dispatcher::NOT_FOUND) {
            return $response->withStatus(404);
        }

        if ($route[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $response->withStatus(405);
        }

        foreach ($route[2] as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $response = call_user_func($route[1], $request, $response);

        return $next($request, $response);
    }

    /**
     * Returns the route dispatcher
     *
     * @throws RuntimeException If the dispatcher cannot be fetched
     * @return Dispatcher
     */
    protected function getDispatcher()
    {
        if (is_callable($this->dispatcher)) {
            $this->dispatcher = call_user_func($this->dispatcher);
        }

        if ($this->dispatcher instanceof Dispatcher) {
            return $this->dispatcher;
        }

        throw new RuntimeException('No FastRoute\\Dispatcher instance has been provided');
    }
}
