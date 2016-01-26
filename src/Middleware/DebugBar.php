<?php declare(strict_types=1);

namespace Psr7Middlewares\Middleware;

use DebugBar\{DebugBar as Bar, StandardDebugBar};
use Psr7Middlewares\{Middleware, Utils};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use RuntimeException;

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
     * Configure whether capture ajax requests to send the data with headers.
     *
     * @param bool $captureAjax
     * 
     * @return self
     */
    public function captureAjax(bool $captureAjax = true): self
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('This middleware needs FormatNegotiator executed before');
        }

        $renderer = $this->debugBar->getJavascriptRenderer();

        //Is an asset?
        $path = $request->getUri()->getPath();
        $renderPath = $renderer->getBaseUrl();

        if (strpos($path, $renderPath) === 0) {
            $file = $renderer->getBasePath().substr($path, strlen($renderPath));

            if (file_exists($file)) {
                $body = Middleware::createStream();
                $body->write(file_get_contents($file));

                return $response->withBody($body);
            }
        }

        $response = $next($request, $response);

        //Fix the render baseUrl
        $renderPath = Utils\Helpers::joinPath(BasePath::getBasePath($request), $renderer->getBaseUrl());
        $renderer->setBaseUrl($renderPath);

        $ajax = Utils\Helpers::isAjax($request);

        //Redirection response
        if (Utils\Helpers::isRedirect($response)) {
            if ($this->debugBar->isDataPersisted() || session_status() === PHP_SESSION_ACTIVE) {
                $this->debugBar->stackData();
            }

        //Html response
        } elseif (FormatNegotiator::getFormat($request) === 'html') {
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
