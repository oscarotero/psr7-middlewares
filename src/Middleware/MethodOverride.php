<?php
namespace Psr7Middlewares\Middleware;

use RuntimeException;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to override the request method using http headers
 */
class MethodOverride
{
    protected $header = 'X-Http-Method-Override';
    protected $get = ['HEAD', 'CONNECT', 'TRACE', 'OPTIONS'];
    protected $post = ['PATCH', 'PUT', 'DELETE', 'COPY', 'LOCK', 'UNLOCK'];

    /**
     * Constructor. Set the trusted ips
     *
     * @param array|null $trusted
     */
    public function __construct(array $trusted = null)
    {
        if ($trusted !== null) {
            $this->trusted($trusted);
        }
    }

    /**
     * Use other header name to override the method
     *
     * @param string $header
     *
     * @return self
     */
    public function header($header)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Set allowed method for GET
     *
     * @return self
     */
    public function get(array $methods)
    {
        $this->get = $methods;

        return $this;
    }

    /**
     * Set allowed method for POST
     *
     * @return self
     */
    public function post(array $methods)
    {
        $this->post = $post;

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
     * Returns the override method
     * 
     * @param ServerRequestInterface $request
     * 
     * @return string|null
     */
    protected function getOverrideMethod(ServerRequestInterface $request)
    {
        $method = $request->getHeaderLine($this->header);

        if (!empty($method) && ($method !== $request->getMethod())) {
            return strtoupper($method);
        }
    }

    /**
     * Returns the allowed override methods
     * 
     * @param ServerRequestInterface $request
     * 
     * @return array
     */
    protected function getAllowedOverrideMethods(ServerRequestInterface $request)
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
