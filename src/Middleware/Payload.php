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
     * Payload constructor.
     *
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
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
        if (!$request->getParsedBody() && in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE', 'COPY', 'LOCK', 'UNLOCK'], true)) {
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
