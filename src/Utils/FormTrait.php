<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\RequestInterface;
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
     * @param string            $input
     * 
     * @return ResponseInterface
     */
    private function insertIntoPostForms(ResponseInterface $response, $input)
    {
        $html = (string) $response->getBody();

        $html = preg_replace_callback(
            '/(<form\s[^>]*method=["\']?POST["\']?[^>]*>)/i',
            function ($match) use ($input) {
                return $match[0].$input;
            },
            $html,
            -1,
            $count
        );

        if (!empty($count)) {
            $body = Middleware::createStream();
            $body->write($html);

            return $response->withBody($body);
        }

        return $response;
    }

    /**
     * Check whether the request is post (or any similar method).
     * 
     * @param RequestInterface $request
     * 
     * @return bool
     */
    private function isPost(RequestInterface $request)
    {
        switch (strtoupper($request->getMethod())) {
            case 'GET':
            case 'HEAD':
            case 'CONNECT':
            case 'TRACE':
            case 'OPTIONS':
                return false;
        }

        return true;
    }
}
