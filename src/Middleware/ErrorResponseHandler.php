<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\RouterTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ErrorResponseHandler
{
    use RouterTrait;

    protected $handler;
    protected $arguments = [];

    /**
     * Constructor
     *
     * @param $handler
     */
    public function __construct($handler)
    {
        $this->handler = $handler;
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
        try {
            $response = $next($request, $response);
        } catch (\Exception $exception) {
            $request = $request->withAttribute('EXCEPTION', $exception);
            $response = $response->withStatus(500);
        }

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            return self::executeTarget($this->handler, $this->arguments, $request, $response);
        }

        return $response;
    }
}
