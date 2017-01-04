<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonSchema
{
    /** @var string[] */
    private $schemas;

    /** @var callable|null */
    private $errorHandler;

    /**
     * JsonSchema constructor.
     *
     * @param string[] $schemas [uri => file] An associative array of HTTP URI to validation schema
     */
    public function __construct(array $schemas)
    {
        $this->schemas = $schemas;
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
        $schema = $this->getSchema($request);

        if ($schema instanceof \SplFileObject) {
            $validator = JsonValidator::fromFile($schema);
            if (is_callable($this->errorHandler)) {
                $validator->errorHandler($this->errorHandler);
            }

            return $validator($request, $response, $next);
        }

        return $next($request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return \SplFileObject|null
     */
    private function getSchema(ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        foreach ($this->schemas as $pattern => $file) {
            if (stripos($path, $pattern) === 0) {
                return new \SplFileObject($this->normalizeFilePath($file));
            }
        }

        return null;
    }

    /**
     * @param string
     *
     * @return string
     */
    private function normalizeFilePath($path)
    {
        if (parse_url($path, PHP_URL_SCHEME)) {
            // The schema file already has a scheme, e.g. `file://` or `vfs://`.
            return $path;
        }

        return 'file://'.$path;
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
