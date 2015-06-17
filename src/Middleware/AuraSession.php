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
     * Creates an instance of this middleware
     *
     * @param string         $name
     * @param SessionFactory $factory
     *
     * @return AuraSession
     */
    public static function create($name = null, SessionFactory $factory = null)
    {
        if ($factory === null) {
            $factory = new SessionFactory();
        }

        return new static($factory, $name);
    }

    /**
     * Constructor
     *
     * @param string         $name
     * @param SessionFactory $factory
     */
    public function __construct(SessionFactory $factory, $name = null)
    {
        $this->factory = $factory;
        $this->name = $name;
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
        $session = $this->factory->newInstance($request->getCookieParams());

        if ($this->name) {
            $session->setName($this->name);
        }

        $request = $request->withAttribute('SESSION', $session);

        return $next($request, $response);
    }
}
