<?php

namespace Psr7Middlewares\Middleware;

use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonValidator
{
    /** @var \stdClass */
    private $schema;

    /**
     * JsonSchema constructor.
     * Consider using one of the following factories instead of invoking the controller directly:
     *  - JsonValidator::fromFile()
     *  - JsonValidator::fromEncodedString()
     *  - JsonValidator::fromDecodedObject()
     *  - JsonValidator::fromArray()
     *
     * @param \stdClass $schema A JSON-decoded object-representation of the schema.
     */
    public function __construct(\stdClass $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param \stdClass $schema
     * @return static|callable
     */
    public static function fromDecodedObject(\stdClass $schema)
    {
        return new static($schema);
    }

    /**
     * @param \SplFileObject $file
     * @return static|callable
     */
    public static function fromFile(\SplFileObject $file)
    {
        $schema = (object)[
            '$ref' => $file->getPathname(),
        ];

        return new static($schema);
    }

    /**
     * @param string $json
     * @return static|callable
     */
    public static function fromEncodedString($json)
    {
        return static::fromDecodedObject(json_decode($json, false));
    }

    /**
     * @param array $json
     * @return static|callable
     */
    public static function fromArray(array $json)
    {
        return static::fromEncodedString(json_encode($json, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \JsonSchema\Exception\ExceptionInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $value = $request->getParsedBody();
        if (!is_object($value)) {
            return $this->invalidateResponse(
                $response,
                sprintf('Parsed body must be an object. Type %s is invalid.', gettype($value))
            );
        }

        $validator = new Validator();
        $validator->check($value, $this->schema);

        if (!$validator->isValid()) {
            return $this->invalidateResponse(
                $response,
                'Unprocessable Entity',
                [
                    'Content-Type' => 'application/json',
                ],
                json_encode($validator->getErrors(), JSON_UNESCAPED_SLASHES)
            );
        }

        return $next($request, $response);
    }

    /**
     * @param ResponseInterface $response
     * @param string $reason
     * @param string[] $headers
     * @param string|null $body
     *
     * @return ResponseInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    private function invalidateResponse(ResponseInterface $response, $reason, array $headers = [], $body = null)
    {
        $response = $response->withStatus(422, $reason);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        if ($body !== null) {
            $stream = $response->getBody();
            $stream->write($body);
        }

        return $response;
    }
}
