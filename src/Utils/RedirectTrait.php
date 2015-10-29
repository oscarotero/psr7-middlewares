<?php

namespace Psr7Middlewares\Utils;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Trait used by all middlewares with redirect() option.
 */
trait RedirectTrait
{
    protected $redirect;

    /**
     * Whether or not return a redirect.
     * 
     * @param bool $redirect
     *
     * @return self
     */
    public function redirect($redirect = true)
    {
        $this->redirect = $redirect;

        return $this;
    }

    /**
     * Returns a redirect response.
     * 
     * @param UriInterface      $uri
     * @param ResponseInterface $response
     */
    protected static function getRedirectResponse(UriInterface $uri, ResponseInterface $response)
    {
        return $response
            ->withStatus(302)
            ->withHeader('Location', (string) $uri)
            ->withBody(Middleware::createStream());
    }
}
