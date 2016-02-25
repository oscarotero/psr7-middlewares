<?php

namespace Psr7Middlewares\Transformers;

use Psr\Http\Message\StreamInterface;
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
     * @param StreamInterface $input
     * @param StreamInterface $output
     * 
     * @return StreamInterface
     */
    public static function js(StreamInterface $input, StreamInterface $output)
    {
        $output->write(JSMinPlus::minify((string) $input));

        return $output;
    }

    /**
     * CSS minifier.
     * 
     * @param StreamInterface $input
     * @param StreamInterface $output
     * 
     * @return StreamInterface
     */
    public static function css(StreamInterface $input, StreamInterface $output)
    {
        $output->write((new CSSmin())->run((string) $input));

        return $output;
    }

    /**
     * HTML minifier.
     * 
     * @param StreamInterface $input
     * @param StreamInterface $output
     * 
     * @return StreamInterface
     */
    public static function html(StreamInterface $input, StreamInterface $output)
    {
        $output->write(Minify_HTML::minify((string) $input, ['jsCleanComments' => true]));

        return $output;
    }
}
