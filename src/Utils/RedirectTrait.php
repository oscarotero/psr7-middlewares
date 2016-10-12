<?php

namespace Psr7Middlewares\Utils;

use Psr7Middlewares\Middleware\BasePath;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

/**
 * Trait used by all middlewares with redirect() option.
 */
trait RedirectTrait
{
    /**
     * @var int|false Redirect HTTP status code
     */
    private $redirectStatus = false;

    /**
     * Set HTTP redirect status code.
     *
     * @param int|false $redirectStatus Redirect HTTP status code
     *
     * @return self
     */
    public function redirect($redirectStatus = 302)
    {
        if (!in_array($redirectStatus, [false, 301, 302], true)) {
            throw new InvalidArgumentException('The redirect status code must be 301, 302 or false');
        }

        $this->redirectStatus = $redirectStatus;

        return $this;
    }

    /**
     * Returns a redirect response.
     *
     * @param ServerRequestInterface $request
     * @param UriInterface           $uri
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    private function getRedirectResponse(ServerRequestInterface $request, UriInterface $uri, ResponseInterface $response)
    {
        //Fix the basePath
        $generator = BasePath::getGenerator($request);

        if ($generator !== null) {
            $uri = $uri->withPath($generator($uri->getPath()));
        }

        return $response
            ->withStatus($this->redirectStatus)
            ->withHeader('Location', (string) $uri);
    }
}
