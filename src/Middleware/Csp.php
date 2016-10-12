<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use ParagonIE\CSPBuilder\CSPBuilder;

/**
 * Middleware to add the Content-Security-Policy header to the responses.
 */
class Csp
{
    /**
     * @var CSPBuilder
     */
    private $csp;

    /**
     * Set CSPBuilder.
     *
     * @param array|null $policies
     */
    public function __construct(array $policies = null)
    {
        if ($policies === null) {
            $policies = [
                'script-src' => ['self' => true],
                'object-src' => ['self' => true],
                'frame-ancestors' => ['self' => true],
            ];
        }

        $this->csp = new CSPBuilder($policies);
    }

    /**
     * Add a source to our allow whitelist.
     *
     * @param string $directive
     * @param string $path
     *
     * @return self
     */
    public function addSource($directive, $path)
    {
        $this->csp->addSource($directive, $path);

        return $this;
    }

    /**
     * Add a directive if it doesn't already exist
     * If it already exists, do nothing.
     *
     * @param string $directive
     * @param mixed  $value
     *
     * @return self
     */
    public function addDirective($directive, $value)
    {
        $this->csp->addDirective($directive, $value);

        return $this;
    }

    /**
     * Whether or not support old browsers (e.g. safari).
     *
     * @param bool $support
     *
     * @return self
     */
    public function supportOldBrowsers($support = true)
    {
        if ($support) {
            $this->csp->enableOldBrowserSupport();
        } else {
            $this->csp->disableOldBrowserSupport();
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
        $response = $next($request, $response);

        $this->csp->compile();

        return $this->csp->injectCSPHeader($response);
    }
}
