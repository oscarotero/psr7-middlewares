<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Minify_HTML as HtmlMinify;
use CSSmin as CssMinify;
use JSMinPlus as JsMinify;
use RuntimeException;

class Minify
{
    use Utils\CacheTrait;

    /**
     * @var bool Minify only cacheable responses
     */
    protected $forCache = false;

    /**
     * @var bool Minify inline css
     */
    protected $inlineCss = true;

    /**
     * @var bool Minify inline js
     */
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
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($this->forCache && !static::isCacheable($request, $response)) {
            return $next($request, $response);
        }

        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('Minify middleware needs FormatNegotiator executed before');
        }

        switch (FormatNegotiator::getFormat($request)) {
            case 'css':
                return $next($request, $this->minifyCss($response));

            case 'js':
                return $next($request, $this->minifyJs($response));

            case 'html':
                return $next($request, $this->minifyHtml($response));

            default:
                return $next($request, $response);
        }
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

        $stream = Middleware::createStream();
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
        $stream = Middleware::createStream();
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
        $stream = Middleware::createStream();
        $stream->write(JsMinify::minify((string) $response->getBody()));

        return $response->withBody($stream);
    }
}
