<?php

namespace Psr7Middlewares\Utils;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

/**
 * Trait used by all middlewares with redirect() option.
 */
trait RedirectTrait
{
    /** 
     * @var int Redirect HTTP status code
     */
    private $redirectStatus;

    /**
     * Set HTTP redirect status code.
     *
     * @param int $redirectStatus Redirect HTTP status code
     * 
     * @return self
     */
    public function redirect($redirectStatus = 302)
    {
        if (!in_array($redirectStatus, [301, 302], true)) {
            throw new InvalidArgumentException('The redirect status code must be 301 or 302');
        }

        $this->redirectStatus = $redirectStatus;

        return $this;
    }

    /**
     * Returns a redirect response.
     * 
     * @param int               $redirectStatus
     * @param UriInterface      $uri
     * @param ResponseInterface $response
     */
    private static function getRedirectResponse($redirectStatus, UriInterface $uri, ResponseInterface $response)
    {
        return $response
            ->withStatus($redirectStatus)
            ->withHeader('Location', (string) $uri)
            ->withBody(Middleware::createStream());
    }
}
