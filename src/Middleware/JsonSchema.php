<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonSchema
{
    /** @var string[] */
    private $schemas;

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

        if (is_object($schema)) {
            $validator = new JsonValidator($schema);
            return $validator($request, $response, $next);
        }

        return $next($request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return object|null
     */
    private function getSchema(ServerRequestInterface $request)
    {
        foreach ($this->schemas as $pattern => $file) {
            $uri = $request->getUri();
            $path = $uri->getPath();

            if (stripos($path, $pattern) === 0) {
                $file = $this->normalizeFilePath($file);

                return (object) [
                    '$ref' => $file,
                ];
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
}
