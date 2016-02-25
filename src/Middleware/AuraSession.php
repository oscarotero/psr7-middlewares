<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Aura\Session\SessionFactory;
use Aura\Session\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuraSession
{
    use Utils\StorageTrait;

    const KEY = 'AURA_SESSION';
    const STORAGE_KEY = 'AURA_SESSION_STORAGE';

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
        return self::getAttribute($request, self::KEY);
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
    public function name($name)
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $session = $this->factory->newInstance($request->getCookieParams());

        if ($this->name !== null) {
            $session->setName($this->name);
        }

        $fragment = $session->getSegment(self::STORAGE_KEY);

        $request = self::setAttribute($request, self::KEY, $session);
        $request = self::startStorage($request, $fragment->get(self::STORAGE_KEY) ?: []);

        $response = $next($request, $response);

        $fragment->set(self::STORAGE_KEY, self::stopStorage($request));

        return $response;
    }
}
