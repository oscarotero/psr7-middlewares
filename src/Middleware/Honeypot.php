<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to span protection using honeypot technique
 */
class Honeypot
{
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
        $response = $next($request, $response);

        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('Honeypot middleware needs FormatNegotiator executed before');
        }

        if (FormatNegotiator::getFormat($request) === 'html') {
            $html = $this->applyToForms((string) $response->getBody());
            $body = Middleware::createStream();
            $body->write($html);

            return $response->withBody($body);
        }

        return $response;
    }

    protected function applyToForms ($html)
    {
        return preg_replace_callback(
            '/(<form [^>]*method="?POST"?[^>]*>)/i',
            function ($match) {
                return $match[0].'<input type="text">';
            },
            $html
        );
    }
}
