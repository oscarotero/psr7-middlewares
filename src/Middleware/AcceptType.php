<?php
namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware returns the client preferred content type
 */
class AcceptType
{
    protected static $mimes = [
        'atom' => ['application/atom+xml'],
        'css' => ['text/css'],
        'html' => ['text/html', 'application/xhtml+xml'],
        'gif' => ['image/gif'],
        'jpg' => ['image/jpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg', 'image/jpg'],
        'js'  => ['text/javascript', 'application/javascript', 'application/x-javascript'],
        'jsonp'  => ['text/javascript', 'application/javascript', 'application/x-javascript'],
        'json' => ['application/json', 'text/json', 'application/x-json'],
        'png' => ['image/png',  'image/x-png'],
        'pdf' => ['application/pdf', 'application/x-download'],
        'rdf' => ['application/rdf+xml'],
        'rss' => ['application/rss+xml'],
        'txt' => ['text/plain'],
        'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
        'zip' => ['application/zip', 'application/x-zip', 'application/x-zip-compressed'],
    ];

    protected $formats = [];
    protected $defaultExtension;
    protected $defaultMime;

    /**
     * Creates an instance of this middleware
     *
     * @param null|array $formats
     * @param string     $default
     */
    public static function create(array $formats = null, $default = 'html')
    {
        if ($formats === null) {
            $formats = array_keys(static::$mimes);
        }

        return new static($formats, $default);
    }

    /**
     * Constructor. Defines de available formats and the default
     *
     * @param array  $formats
     * @param string $default
     */
    public function __construct(array $formats, $default)
    {
        $this->formats = $formats;
        $this->defaultExtension = $default;
        $this->defaultMime = static::$mimes[$default][0];
    }

    /**
     * Execute the middleware
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        //Get from header and extension
        $accept = static::parseAcceptHeader($request->getHeaderLine('Accept'));
        $format = pathinfo($request->getUri()->getPath(), PATHINFO_EXTENSION);

        $request = $request->withAttribute('ACCEPT_TYPE', $accept);

        //Get preferred from extension
        if (in_array($format, $this->formats)) {
            $request = $request
                ->withAttribute('PREFERRED_FORMAT', $format)
                ->withAttribute('PREFERRED_TYPE', static::$mimes[$format][0]);
        } else {
            //Get from Accept header
            $format = $this->getPreferredFormat($accept);

            $request = $request
                ->withAttribute('PREFERRED_FORMAT', $format[0])
                ->withAttribute('PREFERRED_TYPE', $format[1]);
        }

        $response = $next($request, $response);

        //Add the Content-Type header to the response
        if (!$response->hasHeader('Content-Type')) {
            $mimetype = $request->getAttribute('PREFERRED_TYPE');

            return $response->withHeader('Content-Type', "{$mimetype}; charset=UTF-8");
        }

        return $response;
    }

    /**
     * Get the preferred language
     *
     * @param array $accept
     *
     * @return null|string
     */
    protected function getPreferredFormat(array $accept)
    {
        foreach (array_keys($accept) as $mime) {
            foreach (static::$mimes as $extension => $mimetypes) {
                if (!in_array($extension, $this->formats)) {
                    continue;
                }

                if (in_array($mime, $mimetypes)) {
                    return [$extension, $mime];
                }
            }
        }

        return [$this->defaultExtension, $this->defaultMime];
    }

    /**
     * Parses the Accept-Languages header
     *
     * @param string $header
     *
     * @return array
     */
    protected static function parseAcceptHeader($header)
    {
        preg_match_all('/([\w\*-]+\/[\w\*+-]+)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $header, $accept_parse);

        if (count($accept_parse[1])) {
            //create a list like "text/html" => 0.9
            $accept = array_combine(array_map(function ($mime) {
                return strtolower($mime);
            }, $accept_parse[1]), $accept_parse[3]);

            //set default to 1 for any without q factor
            foreach ($accept as &$val) {
                if ($val === '') {
                    $val = 1;
                } else {
                    $val = floatval($val);
                }
            }

            //sort list based on value
            arsort($accept, SORT_NUMERIC);

            return $accept;
        }

        return [];
    }
}
