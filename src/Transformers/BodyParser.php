<?php

namespace Psr7Middlewares\Transformers;

use Psr\Http\Message\ServerRequestInterface;
use DomainException;

/**
 * Generic resolver to parse the body content.
 */
class BodyParser extends Resolver
{
    public function __construct()
    {
        $this->add('application/json', [$this, 'json']);
        $this->add('application/x-www-form-urlencoded', [$this, 'urlencode']);
        $this->add('text/csv', [$this, 'csv']);
    }

    /**
     * @param string $id
     * 
     * @return callable|null
     */
    public function resolve($id)
    {
        foreach ($this->transformers as $contentType => $transformer) {
            if (stripos($id, $contentType) === 0) {
                return $transformer;
            }
        }
    }

    /**
     * JSON parser.
     * 
     * @param ServerRequestInterface $request
     * 
     * @return ServerRequestInterface
     */
    public function json(ServerRequestInterface $request)
    {
        $data = json_decode((string) $request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DomainException(json_last_error_msg());
        }

        return $request->withParsedBody($data);
    }

    /**
     * Parses url-encoded strings.
     * 
     * @param ServerRequestInterface $request
     * 
     * @return ServerRequestInterface
     */
    public function urlencode(ServerRequestInterface $request)
    {
        parse_str((string) $request->getBody(), $data);

        return $request->withParsedBody($data ?: []);
    }

    /**
     * Parses csv strings.
     * 
     * @param ServerRequestInterface $request
     * 
     * @return ServerRequestInterface
     */
    public function csv(ServerRequestInterface $request)
    {
        $body = $request->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $stream = $body->detach();
        $data = [];

        while (($row = fgetcsv($stream)) !== false) {
            $data[] = $row;
        }

        fclose($stream);

        return $request->withParsedBody($data);
    }
}
