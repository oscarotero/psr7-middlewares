<?php

namespace Psr7Middlewares\Transformers;

use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Middleware;
use JSMinPlus;
use CSSmin;
use Minify_HTML;

/**
 * Generic resolver to minify responses.
 */
class Minifier extends Resolver
{
    protected $transformers = [
        'js' => [__CLASS__, 'js'],
        'css' => [__CLASS__, 'css'],
        'html' => [__CLASS__, 'html'],
    ];

    /**
     * Javascript minifier.
     * 
     * @param ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    public static function js(ResponseInterface $response)
    {
        $stream = Middleware::createStream();
        $stream->write(JSMinPlus::minify((string) $response->getBody()));

        return $response->withBody($stream);
    }

    /**
     * CSS minifier.
     * 
     * @param ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    public static function css(ResponseInterface $response)
    {
        $stream = Middleware::createStream();
        $stream->write((new CSSmin())->run((string) $response->getBody()));

        return $response->withBody($stream);
    }

    /**
     * HTML minifier.
     * 
     * @param ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    public static function html(ResponseInterface $response)
    {
        $stream = Middleware::createStream();
        $stream->write(Minify_HTML::minify((string) $response->getBody(), ['jsCleanComments' => true]));

        return $response->withBody($stream);
    }
}
