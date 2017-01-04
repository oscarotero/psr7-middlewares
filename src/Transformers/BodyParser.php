<?php

namespace Psr7Middlewares\Transformers;

use Psr\Http\Message\StreamInterface;
use DomainException;

/**
 * Generic resolver to parse the body content.
 */
class BodyParser extends Resolver
{
    /** @var mixed[] */
    private $options = [
        // When true, returned objects will be converted into associative arrays.
        'forceArray' => true,
    ];

    /**
     * BodyParser constructor.
     *
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->add('application/json', [$this, 'json']);
        $this->add('application/x-www-form-urlencoded', [$this, 'urlencode']);
        $this->add('text/csv', [$this, 'csv']);
        $this->options = array_merge($this->options, $options);
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
     * @return array|object Returns an array when $assoc is true, and an object when $assoc is false
     */
    public function json(StreamInterface $body)
    {
        $assoc = (bool) $this->options['forceArray'];

        $string = (string) $body;

        if ($string === '') {
            return [];
        }

        $data = json_decode($string, $assoc);

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
