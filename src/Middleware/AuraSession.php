<?php
namespace Psr7Middlewares\Middleware;

use Aura\Session\SessionFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuraSession
{
    protected $factory;
    protected $name;

    /**
     * Constructor
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
     * Set the session name
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
     * Set the session factory
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
     * Execute the middleware
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

        $request = $request->withAttribute('SESSION', $session);

        return $next($request, $response);
    }
}
