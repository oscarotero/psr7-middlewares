<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Mobile_Detect;

/**
 * Middleware to detect devices using Mobile_Detect library.
 */
class DetectDevice
{
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
        return Middleware::getAttribute($request, self::KEY);
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $device = new Mobile_Detect($request->getServerParams());

        return $next(Middleware::setAttribute($request, self::KEY, $device), $response);
    }
}
