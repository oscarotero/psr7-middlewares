<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Negotiation\Negotiator;

/**
 * Middleware returns the client preferred format.
 */
class FormatNegotiator
{
    const KEY = 'FORMAT';

    /**
     * @var string Default format
     */
    protected $default = 'html';

    /**
     * @var array Available formats with the available mime types
     */
    protected $formats = [
        'html' => ['text/html', 'application/xhtml+xml'],
        'css' => ['text/css'],
        'gif' => ['image/gif'],
        'png' => ['image/png', 'image/x-png'],
        'jpg' => ['image/jpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg', 'image/jpg'],
        'json' => ['application/json', 'text/json', 'application/x-json'],
        'jsonp' => ['text/javascript', 'application/javascript', 'application/x-javascript'],
        'js' => ['text/javascript', 'application/javascript', 'application/x-javascript'],
        'pdf' => ['application/pdf', 'application/x-download'],
        'rdf' => ['application/rdf+xml'],
        'rss' => ['application/rss+xml'],
        'atom' => ['application/atom+xml'],
        'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip', 'application/x-zip-compressed'],
    ];

    /**
     * Returns the format.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    public static function getFormat(ServerRequestInterface $request)
    {
        return Middleware::getAttribute($request, self::KEY);
    }

    /**
     * Add a new format.
     *
     * @param string $format
     * @param array  $mimeTypes
     *
     * @return self
     */
    public function addFormat($format, array $mimeTypes)
    {
        $this->formats[$format] = $mimeTypes;

        return $this;
    }

    /**
     * Set the default format.
     *
     * @param string $format
     *
     * @return self
     */
    public function defaultFormat($format)
    {
        $this->default = $format;

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
        $format = $this->getFromExtension($request) ?: $this->getFromHeader($request) ?: $this->default;

        if ($format) {
            $request = Middleware::setAttribute($request, self::KEY, $format);
            $response = $response->withHeader('Content-Type', $this->formats[$format][0].'; charset=utf-8');
        }

        return $next($request, $response);
    }

    /**
     * Returns the format using the file extension.
     *
     * @return null|string
     */
    protected function getFromExtension(ServerRequestInterface $request)
    {
        $format = strtolower(pathinfo($request->getUri()->getPath(), PATHINFO_EXTENSION));

        return isset($this->formats[$format]) ? $format : null;
    }

    /**
     * Returns the format using the Accept header.
     *
     * @return null|string
     */
    protected function getFromHeader(ServerRequestInterface $request)
    {
        $accept = $request->getHeaderLine('Accept');

        if (empty($accept)) {
            return;
        }

        $priorities = call_user_func_array('array_merge', array_values($this->formats));
        $accept = (new Negotiator())->getBest($accept, $priorities);

        if ($accept) {
            $accept = $accept->getValue();

            foreach ($this->formats as $extension => $headers) {
                if (in_array($accept, $headers)) {
                    return $extension;
                }
            }
        }
    }
}
