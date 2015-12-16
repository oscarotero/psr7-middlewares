<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Middleware to parse the body.
 */
class Payload
{
    /**
     * @var bool Whether convert the object into associative arrays
     */
    private $associative = false;

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
        $this->associative = (bool) $associative;

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
        return $next($this->handlePayload($request), $response);
    }

    /**
     * Handle the payload.
     *
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    private function handlePayload(ServerRequestInterface $request)
    {
        if ($request->getParsedBody() || !in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'], true)) {
            return $request;
        }

        $contentType = trim($request->getHeaderLine('Content-Type'));

        //json
        if (stripos($contentType, 'application/json') === 0) {
            return $request
                ->withParsedBody($this->parseJson($request->getBody()))
                ->withBody(Middleware::createStream());
        }

        //urlencoded
        if (stripos($contentType, 'application/x-www-form-urlencoded') === 0) {
            return $request
                ->withParsedBody($this->parseUrlEncoded($request->getBody()))
                ->withBody(Middleware::createStream());
        }

        //csv
        if (stripos($contentType, 'text/csv') === 0) {
            return $request
                ->withParsedBody($this->parseCsv($request->getBody()))
                ->withBody(Middleware::createStream());
        }

        return $request;
    }

    /**
     * Parses json.
     * 
     * @param StreamInterface $body
     * 
     * @return array
     */
    private function parseJson(StreamInterface $body)
    {
        return json_decode((string) $body, $this->associative);
    }

    /**
     * Parses url-encoded strings.
     * 
     * @param StreamInterface $body
     * 
     * @return array
     */
    private function parseUrlEncoded(StreamInterface $body)
    {
        parse_str((string) $body, $data);

        return $data ?: [];
    }

    /**
     * Parses csv.
     * 
     * @param StreamInterface $body
     * 
     * @return array
     */
    private function parseCsv(StreamInterface $body)
    {
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $stream = $body->detach();
        $data = [];

        while (($row = fgetcsv($stream)) !== false) {
            $data[] = $row;
        }

        fclose($stream);

        return $data;
    }
}
