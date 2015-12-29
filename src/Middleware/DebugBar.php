<?php

namespace Psr7Middlewares\Middleware;

use DebugBar\DebugBar as Bar;
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to render a debugbar in html responses.
 */
class DebugBar
{
    use Utils\HtmlInjectorTrait;

    private $debugBar;

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
        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('This middleware needs FormatNegotiator executed before');
        }

        $ajax = Utils\Helpers::isAjax($request);

        if (FormatNegotiator::getFormat($request) === 'html') {
            $renderer = $this->debugBar->getJavascriptRenderer();

            ob_start();
            echo '<style>';
            $renderer->dumpCssAssets();
            echo '</style>';

            echo '<script>';
            $renderer->dumpJsAssets();
            echo '</script>';

            echo $renderer->render(!$ajax);

            $response = $this->inject($response, ob_get_clean());
        } elseif ($ajax) {
            $headers = $this->debugBar->getDataAsHeaders();

            foreach ($headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $next($request, $response);
    }
}
