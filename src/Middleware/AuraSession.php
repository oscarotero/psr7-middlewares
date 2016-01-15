<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Aura\Session\{SessionFactory, Session};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};

class AuraSession
{
    const KEY = 'AURA_SESSION';

    /**
     * @var SessionFactory
     */
    private $factory;

    /**
     * @var string|null The session name
     */
    private $name;

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
     * Set the session factory.
     *
     * @param SessionFactory|null $factory
     */
    public function __construct(SessionFactory $factory = null)
    {
        $this->factory = $factory ?: new SessionFactory();
    }

    /**
     * Set the session name.
     *
     * @param string $name
     *
     * @return self
     */
    public function name(string $name): self
    {
        $this->name = $name;

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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $session = $this->factory->newInstance($request->getCookieParams());

        if ($this->name !== null) {
            $session->setName($this->name);
        }

        $request = Middleware::setAttribute($request, self::KEY, $session);

        return $next($request, $response);
    }
}
