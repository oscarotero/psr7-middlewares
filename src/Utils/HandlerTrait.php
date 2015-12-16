<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Utilities used by middlewares with handler options.
 */
trait HandlerTrait
{
    use CallableTrait;

    /**
     * @var callable|string|null The handler used
     */
    private $handler;

    /**
     * Constructor.
     *
     * @param callable|string|null $handler
     */
    public function __construct($handler = null)
    {
        if ($handler !== null) {
            $this->handler($handler);
        }
    }

    /**
     * Set the handler.
     *
     * @param string|callable $handler
     *
     * @return self
     */
    public function handler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Execute the target.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    private function executeHandler(RequestInterface $request, ResponseInterface $response)
    {
        return $this->executeCallable($this->handler, $request, $response);
    }
}
