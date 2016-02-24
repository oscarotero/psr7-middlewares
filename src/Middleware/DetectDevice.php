<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Mobile_Detect;

/**
 * Middleware to detect devices using Mobile_Detect library.
 */
class DetectDevice
{
    use Utils\AttributeTrait;

    const KEY = 'DEVICE';

    /**
     * Returns the device.
     *
     * @param ServerRequestInterface $request
     *
     * @return Mobile_Detect|null
     */
    public static function getDevice(ServerRequestInterface $request)
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
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $device = new Mobile_Detect($request->getServerParams());

        return $next(self::setAttribute($request, self::KEY, $device), $response);
    }
}
