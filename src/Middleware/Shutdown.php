<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to display temporary 503 maintenance pages.
 */
class Shutdown
{
    use Utils\CallableTrait;

    /**
     * @var callable|string The handler used
     */
    private $handler;

    /**
     * Constructor.
     *
     * @param callable|string|null $handler
     */
    public function __construct($handler = null)
    {
        $this->handler = $handler ?: self::class.'::defaultHandler';
    }

    /**
     * Execute the middleware.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response = $this->executeCallable($this->handler, $request, $response);

        return $response->withStatus(503);
    }

    public static function defaultHandler()
    {
        return <<<'EOT'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>503. Site under maintenance</title>
    <style>html{font-family: sans-serif;}</style>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Site under maintenance</h1>
</body>
</html>
EOT;
    }
}
