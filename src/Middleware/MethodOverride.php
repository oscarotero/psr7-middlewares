<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to override the request method using http headers.
 */
class MethodOverride
{
    const HEADER = 'X-Http-Method-Override';

    /**
     * @var array Allowed methods overrided in GET
     */
    private $get = ['HEAD', 'CONNECT', 'TRACE', 'OPTIONS'];

    /**
     * @var array Allowed methods overrided in POST
     */
    private $post = ['PATCH', 'PUT', 'DELETE', 'COPY', 'LOCK', 'UNLOCK'];

    /**
     * @var null|string The POST parameter name
     */
    private $postParam;

    /**
     * @var null|string The GET parameter name
     */
    private $getParam;

    /**
     * Set allowed method for GET.
     *
     * @return self
     */
    public function get(array $methods)
    {
        $this->get = $methods;

        return $this;
    }

    /**
     * Set allowed method for POST.
     *
     * @return self
     */
    public function post(array $methods)
    {
        $this->post = $methods;

        return $this;
    }

    /**
     * Configure the parameters.
     *
     * @param string $name
     * @param bool   $get
     *
     * @return self
     */
    public function parameter($name, $get = true)
    {
        $this->postParam = $name;

        if ($get) {
            $this->getParam = $name;
        }

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
        $method = $this->getOverrideMethod($request);

        if (!empty($method) && $method !== $request->getMethod()) {
            $allowed = $this->getAllowedOverrideMethods($request);

            if (!empty($allowed)) {
                if (in_array($method, $allowed)) {
                    $request = $request->withMethod($method);
                } else {
                    return $response->withStatus(405);
                }
            }
        }

        return $next($request, $response);
    }

    /**
     * Returns the override method.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function getOverrideMethod(ServerRequestInterface $request)
    {
        if ($request->getMethod() === 'POST' && $this->postParam !== null) {
            $params = $request->getParsedBody();

            if (isset($params[$this->postParam])) {
                return strtoupper($params[$this->postParam]);
            }
        } elseif ($request->getMethod() === 'GET' && $this->getParam !== null) {
            $params = $request->getQueryParams();

            if (isset($params[$this->getParam])) {
                return strtoupper($params[$this->getParam]);
            }
        }

        return strtoupper($request->getHeaderLine(self::HEADER));
    }

    /**
     * Returns the allowed override methods.
     *
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getAllowedOverrideMethods(ServerRequestInterface $request)
    {
        switch ($request->getMethod()) {
            case 'GET':
                return $this->get;

            case 'POST':
                return $this->post;

            default:
                return [];
        }
    }
}
