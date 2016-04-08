<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to handle php errors and exceptions.
 */
class ErrorHandler
{
    const KEY = 'EXCEPTION';

    use Utils\CallableTrait;
    use Utils\AttributeTrait;

    /**
     * @var callable|string The handler used
     */
    private $handler;

    /**
     * @var bool Whether or not catch exceptions
     */
    private $catchExceptions = false;

    /**
     * Returns the exception throwed.
     *
     * @param ServerRequestInterface $request
     *
     * @return \Exception|null
     */
    public static function getException(ServerRequestInterface $request)
    {
        return self::getAttribute($request, self::KEY);
    }

    /**
     * Constructor.
     *
     * @param callable|string|null $handler
     */
    public function __construct($handler = null)
    {
        $this->handler = $handler;
    }

    /**
     * Configure the catchExceptions.
     *
     * @param bool $catch
     *
     * @return self
     */
    public function catchExceptions($catch = true)
    {
        $this->catchExceptions = (boolean) $catch;

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
        ob_start();
        $level = ob_get_level();

        try {
            $response = $next($request, $response);
        } catch (\Throwable $exception) {
            if (!$this->catchExceptions) {
                throw $exception;
            }

            $request = self::setAttribute($request, self::KEY, $exception);
            $response = $response->withStatus(500);
        } catch (\Exception $exception) {
            if (!$this->catchExceptions) {
                throw $exception;
            }

            $request = self::setAttribute($request, self::KEY, $exception);
            $response = $response->withStatus(500);
        } finally {
            Utils\Helpers::getOutput($level);
        }

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            return $this->executeCallable($this->getHandler($response), $request, $response);
        }

        return $response;
    }

    private function getHandler(ResponseInterface $response)
    {
        if ($this->handler !== null) {
            return $this->handler;
        }

        switch (Utils\Helpers::getMimeType($response)) {
            case 'text/plain':
            case 'text/css':
            case 'text/javascript':
                return self::CLASS.'::textHandler';

            case 'image/jpeg':
            case 'image/gif':
            case 'image/png':
                return self::CLASS.'::imgHandler';

            case 'image/svg+xml':
                return self::CLASS.'::svgHandler';

            default:
                return self::CLASS.'::htmlHandler';
        }
    }

    /**
     * Returns the error as html
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * 
     * @return string
     */
    public static function htmlHandler(ServerRequestInterface $request, ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        $exception = self::getException($request);

        if ($exception) {
            $message = sprintf('<p>%s</p><pre>%s (%s)</pre>', $exception->getMessage(), $exception->getFile(), $exception->getLine());
        } else {
            $message = '';
        }

        return <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Error {$statusCode}</title>
    <style>html{font-family: sans-serif;}</style>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Error {$statusCode}</h1>
    {$message}
</body>
</html>
EOT;
    }

    /**
     * Returns the error as plain text
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * 
     * @return string
     */
    public static function textHandler(ServerRequestInterface $request, ResponseInterface $response)
    {
        $message = "Error {$response->getStatusCode()}";
        $exception = self::getException($request);

        if ($exception) {
            return $message.sprintf("\n%s\n%s (%s)", $exception->getMessage(), $exception->getFile(), $exception->getLine());
        }

        return $message;
    }

    /**
     * Returns the error as image
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * 
     * @return string
     */
    public static function imgHandler(ServerRequestInterface $request, ResponseInterface $response)
    {
        $message = "Error {$response->getStatusCode()}";
        $exception = self::getException($request);

        if ($exception) {
            $message .= sprintf("\n%s", $exception->getMessage());
        }

        $size = 200;

        $image = imagecreatetruecolor($size, $size);
        $textColor = imagecolorallocate($image, 255, 255, 255);

        foreach(explode("\n", $message) as $n => $paragraph) {
            foreach (str_split($paragraph, intval($size / 10)) as $line => $text) {
                imagestring($image, 5, 10, (($line + 1) * 18 * $n) + 10, $text, $textColor);
            }
        }

        ob_start();

        switch (Utils\Helpers::getMimeType($response)) {
            case 'image/jpeg':
                imagejpeg($image);
                break;

            case 'image/gif':
                imagegif($image);
                break;

            case 'image/png':
                imagepng($image);
                break;
        }

        return ob_get_clean();
    }

    /**
     * Returns the error as svg
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * 
     * @return string
     */
    public function svgHandler(ServerRequestInterface $request, ResponseInterface $response)
    {
        $error = "Error {$response->getStatusCode()}";
        $title = '';

        $exception = self::getException($request);

        if ($exception) {
            $title = $exception->getMessage();
        }

        $size = 100;

        return <<<EOT
        <svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 $size $size">
            <text x="20" y="20" font-family="sans-serif" title="{$title}">
                {$error}
            </text>
        </svg>
EOT;
    }
}
