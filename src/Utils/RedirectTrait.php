<?php declare(strict_types=1);

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
     * @var int|false Redirect HTTP status code
     */
    private $redirectStatus;

    /**
     * Set HTTP redirect status code.
     *
     * @param int|false $redirectStatus Redirect HTTP status code
     * 
     * @return self
     */
    public function redirect($redirectStatus = 302): self
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
     * @param int               $redirectStatus
     * @param UriInterface      $uri
     * @param ResponseInterface $response
     * 
     * @return ResponseInterface
     */
    private static function getRedirectResponse(int $redirectStatus, UriInterface $uri, ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withStatus($redirectStatus)
            ->withHeader('Location', (string) $uri)
            ->withBody(Middleware::createStream());
    }
}
