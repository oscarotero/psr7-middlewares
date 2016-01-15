<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\{Middleware, Utils};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use RuntimeException;
use ReCaptcha\ReCaptcha as GoogleRecaptcha;

/**
 * Middleware to include google recaptcha protection.
 */
class Recaptcha
{
    private $secret;

    /**
     * Constructor. Set the secret token.
     *
     * @param string $secret
     */
    public function __construct(string $secret)
    {
        $this->secret = $secret;
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if (!Middleware::hasAttribute($request, ClientIp::KEY)) {
            throw new RuntimeException('Recaptcha middleware needs ClientIp executed before');
        }

        if (Utils\Helpers::isPost($request)) {
            $recaptcha = new GoogleRecaptcha($this->secret);

            $data = $request->getParsedBody();
            $res = $recaptcha->verify($data['g-recaptcha-response'] ?? '', ClientIp::getIp($request));

            if (!$res->isSuccess()) {
                return $response->withStatus(403);
            }
        }

        return $next($request, $response);
    }
}
