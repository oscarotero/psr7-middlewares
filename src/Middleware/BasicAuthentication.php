<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to create basic http authentication.
 */
class BasicAuthentication
{
    use Utils\AuthenticationTrait;
    use Utils\AttributeTrait;

    const KEY = 'USERNAME';

    /**
     * Returns the username.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    public static function getUsername(ServerRequestInterface $request)
    {
        return self::getAttribute($request, self::KEY);
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
        $authorization = self::parseAuthorizationHeader($request->getHeaderLine('Authorization'));

        if ($authorization && $this->checkUserPassword($authorization['username'], $authorization['password'])) {
            return $next(
                self::setAttribute($request, self::KEY, $authorization['username']),
                $response
            );
        }

        return $response
            ->withStatus(401)
            ->withHeader('WWW-Authenticate', 'Basic realm="'.$this->realm.'"');
    }

    /**
     * Validate the user and password.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function checkUserPassword($username, $password)
    {
        if (!isset($this->users[$username]) || $this->users[$username] !== $password) {
            return false;
        }

        return true;
    }

    /**
     * Parses the authorization header for a basic authentication.
     *
     * @param string $header
     *
     * @return false|array
     */
    private static function parseAuthorizationHeader($header)
    {
        if (strpos($header, 'Basic') !== 0) {
            return false;
        }

        $header = explode(':', base64_decode(substr($header, 6)), 2);

        return [
            'username' => $header[0],
            'password' => isset($header[1]) ? $header[1] : null,
        ];
    }
}
