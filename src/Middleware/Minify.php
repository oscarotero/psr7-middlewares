<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware as Factory;
use Psr7Middlewares\Utils\CacheTrait;
use Minify_HTML as HtmlMinify;
use CSSmin as CssMinify;
use JSMinPlus as JsMinify;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Minify
{
    use CacheTrait;

    protected $forCache = false;
    protected $inlineCss = true;
    protected $inlineJs = true;

    /**
     * Set forCache directive.
     *
     * @param bool $forCache
     *
     * @return self
     */
    public function forCache($forCache = true)
    {
        $this->forCache = $forCache;

        return $this;
    }

    /**
     * Set inlineCss directive.
     *
     * @param bool $inlineCss
     *
     * @return self
     */
    public function inlineCss($inlineCss = true)
    {
        $this->inlineCss = $inlineCss;

        return $this;
    }

    /**
     * Set inlineJs directive.
     *
     * @param bool $inlineJs
     *
     * @return self
     */
    public function inlineJs($inlineJs = true)
    {
        $this->inlineJs = $inlineJs;

        return $this;
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($this->forCache && !static::isCacheable($request, $response)) {
            return $next($request, $response);
        }

        $header = $response->getHeaderLine('Content-Type');
        $extension = strtolower(pathinfo($request->getUri()->getPath(), PATHINFO_EXTENSION));

        if ($extension === 'css' || strpos($header, 'txt/css') !== false) {
            return $next($request, $this->minifyCss($response));
        }

        if ($extension === 'js' || strpos($header, '/javascript') !== false) {
            return $next($request, $this->minifyJs($response));
        }

        if ($extension === 'html' || strpos($header, 'html') !== false) {
            return $next($request, $this->minifyHtml($response));
        }

        return $next($request, $response);
    }

    /**
     * Minify html code.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function minifyHtml(ResponseInterface $response)
    {
        $options = ['jsCleanComments' => true];

        if ($this->inlineCss) {
            $cssMinify = new CssMinify();

            $options['cssMinifier'] = function ($css) use ($cssMinify) {
                return $cssMinify->run($css);
            };
        }

        if ($this->inlineJs) {
            $options['jsMinifier'] = function ($js) {
                return JsMinify::minify($js);
            };
        }

        $stream = Factory::createStream();
        $stream->write(HtmlMinify::minify((string) $response->getBody(), $options));

        return $response->withBody($stream);
    }

    /**
     * Minify css code.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function minifyCss(ResponseInterface $response)
    {
        $stream = Factory::createStream();
        $stream->write((new CssMinify())->run((string) $response->getBody()));

        return $response->withBody($stream);
    }

    /**
     * Minify js code.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function minifyJs(ResponseInterface $response)
    {
        $stream = Factory::createStream();
        $stream->write(JsMinify::minify((string) $response->getBody()));

        return $response->withBody($stream);
    }
}
