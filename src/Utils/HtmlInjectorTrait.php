<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\ResponseInterface;

/**
 * Utilities used by middlewares that inject html code in the responses.
 */
trait HtmlInjectorTrait
{
    use StreamTrait;

    /**
     * Inject some code just before any tag.
     *
     * @param ResponseInterface $response
     * @param string            $code
     * @param string            $tag
     *
     * @return ResponseInterface
     */
    private function inject(ResponseInterface $response, $code, $tag = 'body')
    {
        $html = (string) $response->getBody();
        $pos = strripos($html, "</{$tag}>");

        if ($pos === false) {
            $response->getBody()->write($code);

            return $response;
        }

        $body = self::createStream();
        $body->write(substr($html, 0, $pos).$code.substr($html, $pos));

        return $response->withBody($body);
    }
}
