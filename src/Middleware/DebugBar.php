<?php

namespace Psr7Middlewares\Middleware;

use DebugBar\DebugBar as Bar;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Middleware to render a debugbar in html responses.
 */
class DebugBar
{
    protected $debugBar;

    /**
     * Constructor. Set the debug bar.
     *
     * @param Bar|null $debugBar
     */
    public function __construct(Bar $debugBar = null)
    {
        if ($debugBar !== null) {
            $this->debugBar($debugBar);
        }
    }

    /**
     * Set the debug bar.
     *
     * @param Bar $debugBar
     * 
     * @return self
     */
    public function debugBar(Bar $debugBar)
    {
        $this->debugBar = $debugBar;

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

        if ($this->isValid($request)) {
            $renderer = $this->debugBar->getJavascriptRenderer();

            ob_start();
            echo '<style>';
            $renderer->dumpCssAssets();
            echo '</style>';

            echo '<script>';
            $renderer->dumpJsAssets();
            echo '</script>';

            echo $renderer->render();

            $response->getBody()->write(ob_get_clean());
        }

        return $response;
    }

    /**
     * Check whether the request is valid to insert a debugbar in the response.
     * 
     * @param ServerRequestInterface $request
     * 
     * @return bool
     */
    private function isValid(ServerRequestInterface $request)
    {
        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('DebugBar middleware needs FormatNegotiator executed before');
        }

        //is not html?
        if (FormatNegotiator::getFormat($request) !== 'html') {
            return false;
        }

        //is ajax?
        if (strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest') {
            return false;
        }

        return true;
    }
}
