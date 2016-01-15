<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Middleware;

/**
 * Utilities used by middlewares that manipulates forms.
 */
trait FormTrait
{
    /**
     * Insert content into all POST forms.
     * 
     * @param ResponseInterface $response
     * @param callable          $replace
     * 
     * @return ResponseInterface
     */
    private function insertIntoPostForms(ResponseInterface $response, callable $replace): ResponseInterface
    {
        $html = (string) $response->getBody();
        $html = preg_replace_callback('/(<form\s[^>]*method=["\']?POST["\']?[^>]*>)/i', $replace, $html, -1, $count);

        if (!empty($count)) {
            $body = Middleware::createStream();
            $body->write($html);

            return $response->withBody($body);
        }

        return $response;
    }
}
