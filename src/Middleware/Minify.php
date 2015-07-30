<?php
namespace Psr7Middlewares\Middleware;

use Minify_HTML as HtmlMinify;
use CSSmin as CssMinify;
use JSMinPlus as JsMinify;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Minify
{
    protected $streamCreator;
    protected $forCache;

    /**
     * Creates an instance of this middleware
     *
     * @param callable $streamCreator
     *
     * @return Minify
     */
    public static function create(callable $streamCreator, $forCache = false)
    {
        return new static($streamCreator, $forCache);
    }

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(callable $streamCreator, $forCache)
    {
        $this->streamCreator = $streamCreator;
        $this->forCache = $forCache;
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
        if ($this->forCache && !SaveResponse::mustWrite($request, $response)) {
            return $response;
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
        $stream = call_user_func($this->streamCreator);
        $stream->write(HtmlMinify::minify((string) $response->getBody()));

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
