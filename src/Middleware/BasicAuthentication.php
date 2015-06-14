<?php
namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to create basic http authentication
 */
class BasicAuthentication
{
    protected $users;
    protected $realm;

    /**
     * Creates an instance of this middleware
     *
     * @param array  $users
     * @param string $realm
     */
    public static function create(array $users, $realm = 'Login')
    {
        return new static($users, $realm);
    }

    /**
     * Constructor. Defines de users.
     *
     * @param array  $users [username => password]
     * @param string $realm
     */
    public function __construct(array $users, $realm)
    {
        $this->users = $users;
        $this->realm = $realm;
    }

    /**
     * Execute the middleware
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $authorization = static::parseAuthorizationHeader($request->getHeaderLine('Authorization'));

        if ($authorization && $this->checkUserPassword($authorization['username'], $authorization['password'])) {
            return $next($request, $response);
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
     * @return boolean
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
    protected static function parseAuthorizationHeader($header)
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
