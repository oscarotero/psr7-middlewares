<?php declare(strict_types=1);

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Middleware;

/**
 * Utilities used by middlewares that inject html code in the responses.
 */
trait HtmlInjectorTrait
{
    /**
     * Inject some code just before any tag.
     * 
     * @param ResponseInterface $response
     * @param string            $code
     * @param string            $tag
     * 
     * @return ResponseInterface
     */
    private function inject(ResponseInterface $response, string $code, string $tag = 'body'): ResponseInterface
    {
        $html = (string) $response->getBody();
        $pos = strripos($html, "</{$tag}>");

        if ($pos === false) {
            $response->getBody()->write($code);

            return $response;
        }

        $body = Middleware::createStream();
        $body->write(substr($html, 0, $pos).$code.substr($html, $pos));

        return $response->withBody($body);
    }
}
