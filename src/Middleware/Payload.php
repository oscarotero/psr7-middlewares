<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Transformers;
use Psr7Middlewares\Utils;

/**
 * Middleware to parse the body.
 */
class Payload
{
    use Utils\ResolverTrait;

    /** @var mixed[] */
    private $options;

    /**
     * @var bool Whether or not Middleware\Payload has precedence over existing parsed bodies.
     */
    private $overrideExistingParsedBody = false;

    /**
     * Payload constructor.
     *
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * If the Request object already has a parsedBody, normally Payload will skip parsing. This is not always
     * desirable behavior. Calling this setter allows you to override this behavior.
     */
    public function overrideExistingParsedBody()
    {
        $this->overrideExistingParsedBody = true;
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
        $parsableMethods = ['POST', 'PUT', 'PATCH', 'DELETE', 'COPY', 'LOCK', 'UNLOCK'];

        if (
            ($this->overrideExistingParsedBody || !$request->getParsedBody()) &&
            in_array($request->getMethod(), $parsableMethods, true)
        ) {
            $resolver = $this->resolver ?: new Transformers\BodyParser($this->options);
            $transformer = $resolver->resolve(trim($request->getHeaderLine('Content-Type')));
            if ($transformer) {
                try {
                    $request = $request->withParsedBody($transformer($request->getBody()));
                } catch (\Exception $exception) {
                    return $response->withStatus(400);
                }
            }
        }

        return $next($request, $response);
    }
}
