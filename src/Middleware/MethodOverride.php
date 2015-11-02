<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
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
    protected $get = ['HEAD', 'CONNECT', 'TRACE', 'OPTIONS'];

    /**
     * @var array Allowed methods overrided in POST
     */
    protected $post = ['PATCH', 'PUT', 'DELETE', 'COPY', 'LOCK', 'UNLOCK'];

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
     * Execute the middleware.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $method = $this->getOverrideMethod($request);

        if (!empty($method)) {
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
     * @param RequestInterface $request
     * 
     * @return string|null
     */
    protected function getOverrideMethod(RequestInterface $request)
    {
        $method = $request->getHeaderLine(self::HEADER);

        if (!empty($method) && ($method !== $request->getMethod())) {
            return strtoupper($method);
        }
    }

    /**
     * Returns the allowed override methods.
     * 
     * @param RequestInterface $request
     * 
     * @return array
     */
    protected function getAllowedOverrideMethods(RequestInterface $request)
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
