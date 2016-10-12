<?php

namespace Psr7Middlewares\Middleware;

use DebugBar\DebugBar as Bar;
use DebugBar\StandardDebugBar;
use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to render a debugbar in html responses.
 */
class DebugBar
{
    use Utils\HtmlInjectorTrait;
    use Utils\AttributeTrait;

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
     * Configure whether capture ajax requests to send the data with headers.
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
        $renderer = $this->debugBar->getJavascriptRenderer();

        //Is an asset?
        $path = $request->getUri()->getPath();
        $renderPath = $renderer->getBaseUrl();

        if (strpos($path, $renderPath) === 0) {
            $file = $renderer->getBasePath().substr($path, strlen($renderPath));

            if (file_exists($file)) {
                return $response->withBody(self::createStream($file, 'r'));
            }
        }

        $response = $next($request, $response);

        //Fix the render baseUrl
        $generator = BasePath::getGenerator($request);

        if ($generator) {
            $renderer->setBaseUrl($generator($renderer->getBaseUrl()));
        }

        $ajax = Utils\Helpers::isAjax($request);

        //Redirection response
        if (Utils\Helpers::isRedirect($response)) {
            if ($this->debugBar->isDataPersisted() || session_status() === PHP_SESSION_ACTIVE) {
                $this->debugBar->stackData();
            }

        //Html response
        } elseif (Utils\Helpers::getMimeType($response) === 'text/html') {
            if (!$ajax) {
                $response = $this->inject($response, $renderer->renderHead(), 'head');
            }

            $response = $this->inject($response, $renderer->render(!$ajax), 'body');

        //Ajax response
        } elseif ($ajax && $this->captureAjax) {
            $headers = $this->debugBar->getDataAsHeaders();

            foreach ($headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }
}
