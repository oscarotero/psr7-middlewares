<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use DateTimeImmutable;

/**
 * Middleware to send Expires header.
 */
class Expires
{
    private $expiresDefault = '+1 month';

    private $expires = [
        'text/css' => '+1 year',
        'application/atom+xml' => '+1 hour',
        'application/rdf+xml' => '+1 hour',
        'application/rss+xml' => '+1 hour',
        'application/json' => '+0 seconds',
        'application/ld+json' => '+0 seconds',
        'application/schema+json' => '+0 seconds',
        'application/vnd.geo+json' => '+0 seconds',
        'application/xml' => '+0 seconds',
        'text/xml' => '+0 seconds',
        'image/vnd.microsoft.icon' => '+1 week',
        'image/x-icon' => '+1 week',
        'text/html' => '+0 seconds',
        'application/javascript' => '+1 year',
        'application/x-javascript' => '+1 year',
        'text/javascript' => '+1 year',
        'application/manifest+json' => '+1 week',
        'application/x-web-app-manifest+json' => '+0 seconds',
        'text/cache-manifest' => '+0 seconds',
        'audio/ogg' => '+1 month',
        'image/bmp' => '+1 month',
        'image/gif' => '+1 month',
        'image/jpeg' => '+1 month',
        'image/png' => '+1 month',
        'image/svg+xml' => '+1 month',
        'image/webp' => '+1 month',
        'video/mp4' => '+1 month',
        'video/ogg' => '+1 month',
        'video/webm' => '+1 month',
        'application/vnd.ms-fontobject' => '+1 month',
        'font/eot' => '+1 month',
        'font/opentype' => '+1 month',
        'application/x-font-ttf' => '+1 month',
        'application/font-woff' => '+1 month',
        'application/x-font-woff' => '+1 month',
        'font/woff' => '+1 month',
        'application/font-woff2' => '+1 month',
        'text/x-cross-domain-policy' => '+1 week',
    ];

    /**
     * Add a new expires header.
     *
     * @param string $mime
     * @param string $expires
     */
    public function addExpires($mime, $expires)
    {
        $this->expires[$mime] = $expires;
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
        $response = $next($request, $response);

        $cacheControl = $response->getHeaderLine('Cache-Control') ?: '';

        if (stripos($cacheControl, 'max-age') === false) {
            $mime = Utils\Helpers::getMimeType($response);
            $expires = new DateTimeImmutable(isset($this->expires[$mime]) ? $this->expires[$mime] : $this->expiresDefault);
            $cacheControl .= ' max-age='.($expires->getTimestamp() - time());

            return $response
                ->withHeader('Cache-Control', trim($cacheControl))
                ->withHeader('Expires', $expires->format('D, d M Y H:i:s').' GMT');
        }

        return $response;
    }
}
