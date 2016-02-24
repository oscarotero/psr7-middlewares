<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Middleware to block request from blacklist referrer.
 */
class BlockSpam
{
    private $spammers;
    private $list;

    public function __construct($spammers = null)
    {
        if ($spammers === null) {
            $spammers = __DIR__.'/../../../../vendor/piwik/referrer-spam-blacklist/spammers.txt';
        }

        $this->spammers = $spammers;
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
        if ($this->list === null) {
            if (!is_file($this->spammers)) {
                throw new RuntimeException(sprintf('The spammers file "%s" doest not exists', $this->spammers));
            }

            $this->list = file($this->spammers, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        $referer = parse_url($request->getHeaderLine('Referer'), PHP_URL_HOST);
        $referer = preg_replace('/^(www\.)/i', '', $referer);

        if (in_array($referer, $this->list, true)) {
            return $response->withStatus(403);
        }

        return $next($request, $response);
    }
}
