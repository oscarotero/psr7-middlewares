<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Exception;
use ParagonIE\AntiCSRF\AntiCSRF;

/**
 * Middleware for CSRF protection
 */
class Csrf
{
    use Utils\FormTrait;

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
        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('FormTimestamp middleware needs FormatNegotiator executed before');
        }

        if (FormatNegotiator::getFormat($request) !== 'html') {
            return $next($request, $response);
        }

        $csrf = $this->getCSRF($request);

        if (Utils\Helpers::isPost($request) && (session_status() !== PHP_SESSION_ACTIVE || !$csrf->validateRequest())) {
            return $response->withStatus(403);
        }

        $response = $next($request, $response);

        $defaultAction = self::getDefaultAction($request);

        return $this->insertIntoPostForms($response, function ($match) use ($csrf, $defaultAction) {
            preg_match('/action=["\']?([^"\'\s]+)["\']?/i', $match[0], $matches);

            $action = empty($matches[1]) ? $defaultAction : $matches[1];

            return $match[0].$csrf->insertToken($action, false);
        });

        return $response;
    }

    /**
     * Creates an instance of AntiCSRF
     * 
     * @param ServerRequestInterface $request
     * 
     * @return AntiCSRF
     */
    private function getCSRF(ServerRequestInterface $request)
    {
        $post = $request->getParsedBody();
        $server = $request->getServerParams();

        if (session_status() === PHP_SESSION_ACTIVE) {
            $session =& $_SESSION;
        } else {
            $session = [];
        }

        return new AntiCSRF($post, $session, $server);
    }

    private static function getDefaultAction(ServerRequestInterface $request)
    {
        $server = $request->getServerParams();

        return isset($server['REQUEST_URI']) ? $server['REQUEST_URI'] : $server['SCRIPT_NAME'];
    }
}
