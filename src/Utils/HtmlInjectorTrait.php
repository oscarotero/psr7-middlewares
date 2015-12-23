<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Middleware\FormatNegotiator;
use Psr7Middlewares\Middleware;

/**
 * Utilities used by middlewares that inject html code in the responses
 */
trait HtmlInjectorTrait
{
    /**
     * Inject some code just before any tag
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

        $body = Middleware::createStream();
        $body->write(substr($html, 0, $pos).$code.substr($html, $pos + 1));

        return $response->withBody($body);
    }

    /**
     * Check whether the request is valid to insert html in the response.
     * 
     * @param ServerRequestInterface $request
     * 
     * @return bool
     */
    private function isInjectable(ServerRequestInterface $request)
    {
        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('This middleware needs FormatNegotiator executed before');
        }

        //Must be html
        if (FormatNegotiator::getFormat($request) !== 'html') {
            return false;
        }

        //And not ajax
        if (strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest') {
            return false;
        }

        return true;
    }
}
