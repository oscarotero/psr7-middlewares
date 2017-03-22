<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to redirect to https protocol.
 */
class Https
{
    use Utils\RedirectTrait;

    const HEADER = 'Strict-Transport-Security';

    /**
     * @var bool Add or remove https
     */
    private $addHttps;

    /**
     * @param int One year by default
     */
    private $maxAge = 31536000;

    /**
     * @param bool Whether include subdomains
     */
    private $includeSubdomains = false;

    /**
     * @param bool Whether check the headers
     */
    private $checkHttpsForward = false;

    /**
     * Set basic config.
     *
     * @param bool $addHttps
     */
    public function __construct($addHttps = true)
    {
        $this->addHttps = (bool) $addHttps;
        $this->redirect(301);
    }

    /**
     * Configure the max-age HSTS in seconds.
     *
     * @param int $maxAge
     *
     * @return self
     */
    public function maxAge($maxAge)
    {
        $this->maxAge = $maxAge;

        return $this;
    }

    /**
     * Configure the includeSubDomains HSTS directive.
     *
     * @param bool $includeSubdomains
     *
     * @return self
     */
    public function includeSubdomains($includeSubdomains = true)
    {
        $this->includeSubdomains = $includeSubdomains;

        return $this;
    }

    /**
     * Configure whether check the following headers before redirect:
     * X-Forwarded-Proto: https
     * X-Forwarded-Port: 443.
     *
     * @param bool $checkHttpsForward
     *
     * @return self
     */
    public function checkHttpsForward($checkHttpsForward = true)
    {
        $this->checkHttpsForward = $checkHttpsForward;

        return $this;
    }

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
        $uri = $request->getUri();

        if ($this->addHttps) {
            if (strtolower($uri->getScheme()) !== 'https') {
                $uri = $uri->withScheme('https')->withPort(443);

                if ($this->redirectStatus !== false && (!$this->checkHttpsForward || ($request->getHeaderLine('X-Forwarded-Proto') !== 'https' && $request->getHeaderLine('X-Forwarded-Port') !== '443'))) {
                    return $this->getRedirectResponse($request, $uri, $response);
                }

                $request = $request->withUri($uri);
            }

            if (!empty($this->maxAge)) {
                $response = $response->withHeader(self::HEADER, sprintf('max-age=%d%s', $this->maxAge, $this->includeSubdomains ? ';includeSubDomains' : ''));
            }
        } else {
            if (strtolower($uri->getScheme()) !== 'http') {
                $uri = $uri->withScheme('http')->withPort(80);

                if ($this->redirectStatus !== false && (!$this->checkHttpsForward || ($request->getHeaderLine('X-Forwarded-Proto') !== 'http' && $request->getHeaderLine('X-Forwarded-Port') !== '80'))) {
                    return $this->getRedirectResponse($request, $uri, $response);
                }

                $request = $request->withUri($uri);
            }
        }

        $response = $next($request, $response);

        if (Utils\Helpers::isRedirect($response)) {
            if ($this->addHttps) {
                return $response->withHeader('Location', str_replace('http://', 'https://', $response->getHeaderLine('Location')));
            }

            return $response->withHeader('Location', str_replace('https://', 'http://', $response->getHeaderLine('Location')));
        }

        return $response;
    }
}
