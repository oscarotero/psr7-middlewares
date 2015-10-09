<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to parse the body.
 */
class Payload
{
    protected $associative = false;

    /**
     * To convert the objects into associative arrays.
     *
     * @see http://php.net/json_decode
     *
     * @param bool $associative
     *
     * @return self
     */
    public function associative($associative = true)
    {
        $this->associative = (boolean) $associative;

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
        return $next($this->handlePayload($request), $response);
    }

    /**
     * Handle the payload.
     *
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    protected function handlePayload(ServerRequestInterface $request)
    {
        if ($request->getParsedBody() || !in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'], true)) {
            return $request;
        }

        $contentType = trim($request->getHeaderLine('Content-Type'));

        //json
        if (stripos($contentType, 'application/json') === 0) {
            return $request->withParsedBody(json_decode((string) $request->getBody(), $this->associative));
        }

        //urlencoded
        if (stripos($contentType, 'application/x-www-form-urlencoded') === 0) {
            parse_str((string) $request->getBody(), $data);

            return $request->withParsedBody($data ?: []);
        }
    }
}
