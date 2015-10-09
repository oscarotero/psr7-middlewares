<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Aura\Session\SessionFactory;
use Aura\Session\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuraSession
{
    const KEY = 'AURA_SESSION';

    protected $factory;
    protected $name;

    /**
     * Returns the session instance.
     *
     * @param ServerRequestInterface $request
     *
     * @return Session|null
     */
    public static function getSession(ServerRequestInterface $request)
    {
        return Middleware::getAttribute($request, self::KEY);
    }

    /**
     * Constructor.
     *
     * @param SessionFactory|null $factory
     */
    public function __construct(SessionFactory $factory = null)
    {
        if ($factory !== null) {
            $this->factory($factory);
        }
    }

    /**
     * Set the session name.
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
     * Set the session factory.
     *
     * @param SessionFactory $factory
     *
     * @return self
     */
    public function factory(SessionFactory $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $factory = $this->factory ?: new SessionFactory();
        $session = $factory->newInstance($request->getCookieParams());

        if ($this->name !== null) {
            $session->setName($this->name);
        }

        $request = Middleware::setAttribute($request, self::KEY, $session);

        return $next($request, $response);
    }
}
