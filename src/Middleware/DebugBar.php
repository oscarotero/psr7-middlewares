<?php

namespace Psr7Middlewares\Middleware;

use DebugBar\DebugBar as Bar;
use DebugBar\StandardDebugBar;
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

    /**
     * @var Bar|null The debugbar
     */
    private $debugBar;

    /**
     * @var bool Whether send data using headers in ajax requests
     */
    private $captureAjax = false;

    /**
     * Constructor. Set the debug bar.
     *
     * @param Bar|null $debugBar
     */
    public function __construct(Bar $debugBar = null)
    {
        $this->debugBar = $debugBar ?: new StandardDebugBar();
    }

    /**
     * Configure whether capture ajax requests to send the data with headers
     *
     * @param bool $captureAjax
     * 
     * @return self
     */
    public function captureAjax($captureAjax = true)
    {
        $this->captureAjax = $captureAjax;

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

        //Redirection response
        if (Utils\Helpers::isRedirect($response)) {
            if ($this->debugBar->isDataPersisted() || session_status() === PHP_SESSION_ACTIVE) {
                $this->debugBar->stackData();
            }

        //Html response
        } elseif (FormatNegotiator::getFormat($request) === 'html') {
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
        
        //Ajax response
        } elseif ($ajax && $this->captureAjax) {
            $headers = $this->debugBar->getDataAsHeaders();

            foreach ($headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $next($request, $response);
    }
}
