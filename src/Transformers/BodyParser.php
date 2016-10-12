<?php

namespace Psr7Middlewares\Transformers;

use Psr\Http\Message\StreamInterface;
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
     * @param StreamInterface $body
     *
     * @return array
     */
    public function json(StreamInterface $body)
    {
        $data = json_decode((string) $body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DomainException(json_last_error_msg());
        }

        return $data ?: [];
    }

    /**
     * Parses url-encoded strings.
     *
     * @param StreamInterface $body
     *
     * @return array
     */
    public function urlencode(StreamInterface $body)
    {
        parse_str((string) $body, $data);

        return $data ?: [];
    }

    /**
     * Parses csv strings.
     *
     * @param StreamInterface $body
     *
     * @return array
     */
    public function csv(StreamInterface $body)
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
