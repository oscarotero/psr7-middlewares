<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils\CacheTrait;

use Minify_HTML as HtmlMinify;
use CSSmin as CssMinify;
use JSMinPlus as JsMinify;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Minify
{
    use CacheTrait;

    protected $streamCreator;
    protected $options = [
        'forCache' => false,
        'inlineCss' => true,
        'inlineJs' => true
    ];

    /**
     * Creates an instance of this middleware
     *
     * @param callable $streamCreator
     *
     * @return Minify
     */
    public static function create(callable $streamCreator, array $options = array())
    {
        return new static($streamCreator, $options);
    }

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(callable $streamCreator, array $options)
    {
        $this->streamCreator = $streamCreator;
        $this->options = $options + $this->options;
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
        if ($this->options['forCache'] && !static::isCacheable($request, $response)) {
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
     * Minify html code
     * 
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function minifyHtml(ResponseInterface $response)
    {
        $options = ['jsCleanComments' => true];

        if ($this->options['inlineCss']) {
            $cssMinify = new CssMinify();

            $options['cssMinifier'] = function ($css) use ($cssMinify) {
                return $cssMinify->run($css);
            };
        }

        if ($this->options['inlineJs']) {
            $options['jsMinifier'] = function ($js) {
                return JsMinify::minify($js);
            };
        }

        $stream = call_user_func($this->streamCreator);
        $stream->write(HtmlMinify::minify((string) $response->getBody(), $options));

        return $response->withBody($stream);
    }

    /**
     * Minify css code
     * 
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function minifyCss(ResponseInterface $response)
    {
        $stream = call_user_func($this->streamCreator);
        $stream->write((new CssMinify())->run((string) $response->getBody()));

        return $response->withBody($stream);
    }

    /**
     * Minify js code
     * 
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function minifyJs(ResponseInterface $response)
    {
        $stream = call_user_func($this->streamCreator);
        $stream->write(JsMinify::minify((string) $response->getBody()));

        return $response->withBody($stream);
    }
}
