<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to include a php with the response.
 */
class IncludeResponse
{
    use Utils\FileTrait;
    use Utils\StreamTrait;

    private $continueOnError = false;

    /**
     * Configure if continue to the next middleware if the response has not found.
     *
     * @param bool $continueOnError
     *
     * @return self
     */
    public function continueOnError($continueOnError = true)
    {
        $this->continueOnError = $continueOnError;

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
        $file = $this->getFilename($request, 'php');

        if (!is_file($file)) {
            if ($this->continueOnError) {
                return $next($request, $response);
            }

            return $response->withStatus(404);
        }

        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
            $level = ob_get_level();
            ob_start();
            self::includeFile($file);
            $body = self::createStream();
            $body->write(Utils\Helpers::getOutput($level));

            foreach (headers_list() as $header) {
                list($name, $value) = array_map('trim', explode(':', $header, 2));
                $response = $response->withHeader($name, $value);
            }

            return $response->withBody($body);
        }

        return $next($request, $response);
    }

    /**
     * Includes a php file from a static context.
     *
     * @param string $file
     *
     * @return string
     */
    private static function includeFile($file)
    {
        include $file;
    }
}
