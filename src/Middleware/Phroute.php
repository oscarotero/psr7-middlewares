<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Phroute\Phroute\Dispatcher;

class Phroute
{

    /**
     * @var Dispatcher Phroute dispatcher
     */
    private $router;

    /**
     * Set the Dispatcher instance.
     *
     * @param Dispatcher|null $router
     */
    public function __construct(Dispatcher $router)
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) {
        try {
            ob_start();
            $this->router->dispatch($request->getMethod(), $request->getUri()->getPath());
            $bufferedBody = ob_get_clean();
            $response->getBody()->write($bufferedBody);
            $response = $response->withStatus(200);
        }
        catch (\Phroute\Phroute\Exception\HttpRouteNotFoundException $e) {
                $reponse = new \Zend\Diactoros\Response\HtmlResponse($e->getMessage(), 404);
        }
        catch (\Phroute\Phroute\Exception\BadRouteException $e) {
            $allowedMethods = $routeInfo[1];
                $reponse = new \Zend\Diactoros\Response\HtmlResponse($e->getMessage(), 405);
        }
        return $next($request, $response);
    }
}
