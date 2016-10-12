<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to redirect to force www subdomain or remove it.
 */
class Www
{
    use Utils\RedirectTrait;

    /**
     * @var bool Add or remove www
     */
    private $addWww;

    /**
     * Configure whether the www subdomain should be added or removed.
     *
     * @param bool $addWww
     */
    public function __construct($addWww = false)
    {
        $this->addWww = (bool) $addWww;
        $this->redirect(301);
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
        $host = $uri->getHost();

        if ($this->addWww) {
            if ($this->canAddWww($host)) {
                $host = "www.{$host}";
            }
        } elseif (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        //redirect
        if ($this->redirectStatus !== false && ($uri->getHost() !== $host)) {
            return $this->getRedirectResponse($request, $uri->withHost($host), $response);
        }

        return $next($request->withUri($uri->withHost($host)), $response);
    }

    /**
     * Check whether the domain can add a www. subdomain.
     * Returns false if:
     * - the host is "localhost"
     * - the host is a ip
     * - the host has already a subdomain, for example "subdomain.example.com".
     *
     * @param string $host
     *
     * @return bool
     */
    private function canAddWww($host)
    {
        if (empty($host) || filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        $host = explode('.', $host);

        switch (count($host)) {
            case 1: //localhost
                return false;

            case 2: //example.com
                return true;

            case 3:
                //example.co.uk
                if ($host[1] === 'co') {
                    return true;
                }

            default:
                return false;
        }
    }
}
