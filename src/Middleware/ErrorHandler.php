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
    use Utils\StreamTrait;

    /**
     * @var callable|string The handler used
     */
    private $handler;

    /**
     * @var callable|null The status code validator
     */
    private $statusCodeValidator;

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
        $this->catchExceptions = (bool) $catch;

        return $this;
    }

    /**
     * Configure the status code validator.
     *
     * @param callable $statusCodeValidator
     *
     * @return self
     */
    public function statusCode(callable $statusCodeValidator)
    {
        $this->statusCodeValidator = $statusCodeValidator;

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

        if ($this->isError($response->getStatusCode())) {
            $callable = $this->handler ?: [$this, 'defaultHandler'];
            $body = self::createStream($response->getBody());

            return $this->executeCallable($callable, $request, $response->withBody($body));
        }

        return $response;
    }

    /**
     * Check whether the status code represents an error or not.
     *
     * @param int $statusCode
     *
     * @return bool
     */
    private function isError($statusCode)
    {
        if ($this->statusCodeValidator) {
            return call_user_func($this->statusCodeValidator, $statusCode);
        }

        return $statusCode >= 400 && $statusCode < 600;
    }

    /**
     * Default handler.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return string
     */
    private function defaultHandler(ServerRequestInterface $request, ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        $exception = self::getException($request);
        $message = $exception ? $exception->getMessage() : '';

        switch (Utils\Helpers::getMimeType($response)) {
            case 'text/plain':
            case 'text/css':
            case 'text/javascript':
                return self::errorText($statusCode, $message);

            case 'image/jpeg':
                return self::errorImage($statusCode, $message, 'imagejpeg');

            case 'image/gif':
                return self::errorImage($statusCode, $message, 'imagegif');

            case 'image/png':
                return self::errorImage($statusCode, $message, 'imagepng');

            case 'image/svg+xml':
                return self::errorSvg($statusCode, $message);

            case 'application/json':
                return self::errorJson($statusCode, $message);

            case 'text/xml':
                return self::errorXml($statusCode, $message);

            default:
                return self::errorHtml($statusCode, $message);
        }
    }

    /**
     * Print the error as plain text.
     *
     * @param int    $statusCode
     * @param string $message
     *
     * @return string
     */
    private static function errorText($statusCode, $message)
    {
        return sprintf("Error %s\n%s", $statusCode, $message);
    }

    /**
     * Print the error as svg image.
     *
     * @param int    $statusCode
     * @param string $message
     *
     * @return string
     */
    private static function errorSvg($statusCode, $message)
    {
        return <<<EOT
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="50" viewBox="0 0 200 50">
    <text x="20" y="30" font-family="sans-serif" title="{$message}">
        Error {$statusCode}
    </text>
</svg>
EOT;
    }

    /**
     * Print the error as html.
     *
     * @param int    $statusCode
     * @param string $message
     *
     * @return string
     */
    private static function errorHtml($statusCode, $message)
    {
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
     * Print the error as image.
     *
     * @param int    $statusCode
     * @param string $message
     * @param string $output
     *
     * @return string
     */
    private static function errorImage($statusCode, $message, $output)
    {
        $size = 200;
        $image = imagecreatetruecolor($size, $size);
        $textColor = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, 10, 10, "Error {$statusCode}", $textColor);

        foreach (str_split($message, intval($size / 10)) as $line => $text) {
            imagestring($image, 5, 10, ($line * 18) + 28, $text, $textColor);
        }

        ob_start();
        $output($image);

        return ob_get_clean();
    }

    /**
     * Print the error as json.
     *
     * @param int    $statusCode
     * @param string $message
     *
     * @return string
     */
    private static function errorJson($statusCode, $message)
    {
        $output = ['error' => $statusCode];

        if (!empty($message)) {
            $output['message'] = $message;
        }

        return json_encode($output);
    }

    /**
     * Print the error as xml.
     *
     * @param int    $statusCode
     * @param string $message
     *
     * @return string
     */
    private static function errorXml($statusCode, $message)
    {
        return <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<error>
    <code>{$statusCode}</code>
    <message>{$message}</message>
</error>
EOT;
    }
}
