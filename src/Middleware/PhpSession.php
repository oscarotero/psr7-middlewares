<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Middleware to use php session.
 */
class PhpSession
{
    use Utils\StorageTrait;

    const STORAGE_KEY = 'PHP_SESSION_STORAGE';

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $id;

    /**
     * Constructor. Defines de session name.
     *
     * @param null|string $name
     */
    public function __construct($name = null)
    {
        if ($name !== null) {
            $this->name($name);
        }
    }

    /**
     * Configure the session name.
     *
     * @param string $name
     *
     * @return self
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Configure the session id.
     *
     * @param string $id
     *
     * @return self
     */
    public function id($id)
    {
        $this->id = $id;

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
        if (session_status() === PHP_SESSION_DISABLED) {
            throw new RuntimeException('PHP sessions are disabled');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Failed to start the session: already started by PHP.');
        }

        //Session name
        $name = $this->name ?: session_name();
        session_name($name);

        //Session id
        $id = $this->id;

        if (empty($id)) {
            $cookies = $request->getCookieParams();

            if (!empty($cookies[$name])) {
                $id = $cookies[$name];
            }
        }

        if (!empty($id)) {
            session_id($id);
        }

        session_start();

        $request = self::startStorage($request, isset($_SESSION[self::STORAGE_KEY]) ? $_SESSION[self::STORAGE_KEY] : []);
        $response = $next($request, $response);

        if ((session_status() === PHP_SESSION_ACTIVE) && (session_name() === $name)) {
            $_SESSION[self::STORAGE_KEY] = self::stopStorage($request);
            session_write_close();
        }

        return $response;
    }
}
