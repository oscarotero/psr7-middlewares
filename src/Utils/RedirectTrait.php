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
    /** @var int Redirect HTTP status code */
    protected $redirectStatus;

    /**
     * Set HTTP redirect status code.
     *
     * @param int $redirectStatus Redirect HTTP status code
     * @link http://php.net/manual/ru/http.constants.php#http.constants.redirect
     * 
     * @return self
     */
    public function redirect($redirectStatus = HTTP_REDIRECT_FOUND)
    {
        $this->redirectStatus = $redirectStatus;

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
            ->withStatus($this->redirectStatus)
            ->withHeader('Location', (string) $uri)
            ->withBody(Middleware::createStream());
    }
}
