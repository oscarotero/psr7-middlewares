<?php

namespace Psr7Middlewares\Transformers;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Generic resolver to parse the body content.
 */
class BodyParser implements ResolverInterface
{
    /**
     * @var bool Whether convert the object into associative arrays
     */
    private $associative = true;

    /**
     * @var array List of all parseable content types
     */
    protected $contentTypes = [
        'json' => 'application/json',
        'urlencode' => 'application/x-www-form-urlencoded',
        'csv' => 'text/csv',
    ];

    /**
     * @param string $id
     * 
     * @return callable|null
     */
    public function resolve($id)
    {
        foreach ($this->contentTypes as $method => $contentType) {
            if (stripos($id, $contentType) === 0) {
                return [$this, $method];
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
        $data = json_decode((string) $request->getBody(), $this->associative);

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
