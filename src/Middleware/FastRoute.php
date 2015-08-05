<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\RouterTrait;
use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class FastRoute
{
    use RouterTrait;

    protected $dispatcher;
    protected $arguments = [];

    /**
     * Constructor
     * You can specify the Dispatcher instance or a callable to fetch it in lazy mode
     *
     * @param Dispatcher|callable $dispatcher
     */
    public function __construct($dispatcher)
    {
        $this->dispatcher = $dispatcher;
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

        $response = self::executeTarget($route[1], $this->arguments, $request, $response);

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
