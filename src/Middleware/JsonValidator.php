<?php

namespace Psr7Middlewares\Middleware;

use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils\AttributeTrait;
use Psr7Middlewares\Utils\CallableTrait;

class JsonValidator
{
    const KEY = 'JSON_VALIDATION_ERRORS';

    use AttributeTrait;
    use CallableTrait;

    /** @var \stdClass */
    private $schema;

    /** @var callable */
    private $errorHandler;

    /**
     * JsonSchema constructor.
     * Consider using one of the following factories instead of invoking the controller directly:
     *  - JsonValidator::fromFile()
     *  - JsonValidator::fromEncodedString()
     *  - JsonValidator::fromDecodedObject()
     *  - JsonValidator::fromArray().
     *
     * @param \stdClass $schema A JSON-decoded object-representation of the schema
     */
    public function __construct(\stdClass $schema)
    {
        $this->schema = $schema;
        $this->errorHandler = [$this, 'defaultErrorHandler'];
    }

    /**
     * @param \stdClass $schema
     *
     * @return static|callable
     */
    public static function fromDecodedObject(\stdClass $schema)
    {
        return new static($schema);
    }

    /**
     * @param \SplFileObject $file
     *
     * @return static|callable
     */
    public static function fromFile(\SplFileObject $file)
    {
        $schema = (object) [
            '$ref' => $file->getPathname(),
        ];

        return new static($schema);
    }

    /**
     * @param string $json
     *
     * @return static|callable
     */
    public static function fromEncodedString($json)
    {
        return static::fromDecodedObject(json_decode($json, false));
    }

    /**
     * @param array $json
     *
     * @return static|callable
     */
    public static function fromArray(array $json)
    {
        return static::fromEncodedString(json_encode($json, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Returns the request's JSON validation errors.
     *
     * @param ServerRequestInterface $request
     *
     * @return array|null
     */
    public static function getErrors(ServerRequestInterface $request)
    {
        return self::getAttribute($request, self::KEY);
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \JsonSchema\Exception\ExceptionInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $value = $request->getParsedBody();
        if (!is_object($value)) {
            $request = self::setAttribute($request, self::KEY, [
                sprintf('Parsed body must be an object. Type %s is invalid.', gettype($value)),
            ]);

            return $this->executeCallable($this->errorHandler, $request, $response);
        }

        $validator = new Validator();
        $validator->check($value, $this->schema);

        if (!$validator->isValid()) {
            $request = self::setAttribute($request, self::KEY, $validator->getErrors());

            return $this->executeCallable($this->errorHandler, $request, $response);
        }

        return $next($request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    private function defaultErrorHandler(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $response = $response->withStatus(422, 'Unprocessable Entity')
            ->withHeader('Content-Type', 'application/json');

        $middlewareAttribute = $request->getAttribute(Middleware::KEY, []);

        if (isset($middlewareAttribute[self::KEY])) {
            /** @var ResponseInterface $response */
            $stream = $response->getBody();
            $stream->write(json_encode($middlewareAttribute[self::KEY], JSON_UNESCAPED_SLASHES));
        }

        return $response;
    }

    /**
     * Has the following method signature:
     * function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {}.
     *
     * Validation errors are stored in a middleware attribute:
     * $request->getAttribute(Middleware::KEY, [])[JsonValidator::KEY];
     *
     * @param callable $errorHandler
     */
    public function errorHandler(callable $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }
}
