<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Negotiation\Negotiator;

/**
 * Middleware returns the client preferred format.
 */
class FormatNegotiator
{
    use Utils\NegotiateTrait;
    use Utils\AttributeTrait;

    const KEY = 'FORMAT';

    /**
     * @var string Default format
     */
    private $default;

    /**
     * @var array Available formats with the mime types
     */
    private $formats = [
        //text
        'html' => [['html', 'htm', 'php'], ['text/html', 'application/xhtml+xml']],
        'txt' => [['txt'], ['text/plain']],
        'css' => [['css'], ['text/css']],
        'json' => [['json'], ['application/json', 'text/json', 'application/x-json']],
        'jsonp' => [['jsonp'], ['text/javascript', 'application/javascript', 'application/x-javascript']],
        'js' => [['js'], ['text/javascript', 'application/javascript', 'application/x-javascript']],

        //xml
        'rdf' => [['rdf'], ['application/rdf+xml']],
        'rss' => [['rss'], ['application/rss+xml']],
        'atom' => [['atom'], ['application/atom+xml']],
        'xml' => [['xml'], ['text/xml', 'application/xml', 'application/x-xml']],

        //images
        'bmp' => [['bmp'], ['image/bmp']],
        'gif' => [['gif'], ['image/gif']],
        'png' => [['png'], ['image/png', 'image/x-png']],
        'jpg' => [['jpg', 'jpeg', 'jpe'], ['image/jpeg', 'image/jpg']],
        'svg' => [['svg', 'svgz'], ['image/svg+xml']],
        'psd' => [['psd'], ['image/vnd.adobe.photoshop']],
        'eps' => [['ai', 'eps', 'ps'], ['application/postscript']],
        'ico' => [['ico'], ['image/x-icon', 'image/vnd.microsoft.icon']],

        //audio/video
        'mov' => [['mov', 'qt'], ['video/quicktime']],
        'mp3' => [['mp3'], ['audio/mpeg']],
        'mp4' => [['mp4'], ['video/mp4']],
        'ogg' => [['ogg'], ['audio/ogg']],
        'ogv' => [['ogv'], ['video/ogg']],
        'webm' => [['webm'], ['video/webm']],
        'webp' => [['webp'], ['image/webp']],

        //fonts
        'eot' => [['eot'], ['application/vnd.ms-fontobject']],
        'otf' => [['otf'], ['font/opentype', 'application/x-font-opentype']],
        'ttf' => [['ttf'], ['font/ttf', 'application/font-ttf', 'application/x-font-ttf']],
        'woff' => [['woff'], ['font/woff', 'application/font-woff', 'application/x-font-woff']],
        'woff2' => [['woff2'], ['font/woff2', 'application/font-woff2', 'application/x-font-woff2']],

        //other formats
        'pdf' => [['pdf'], ['application/pdf', 'application/x-download']],
        'zip' => [['zip'], ['application/zip', 'application/x-zip', 'application/x-zip-compressed']],
        'rar' => [['rar'], ['application/rar', 'application/x-rar', 'application/x-rar-compressed']],
        'exe' => [['exe'], ['application/x-msdownload']],
        'msi' => [['msi'], ['application/x-msdownload']],
        'cab' => [['cab'], ['application/vnd.ms-cab-compressed']],
        'doc' => [['doc'], ['application/msword']],
        'rtf' => [['rtf'], ['application/rtf']],
        'xls' => [['xls'], ['application/vnd.ms-excel']],
        'ppt' => [['ppt'], ['application/vnd.ms-powerpoint']],
        'odt' => [['odt'], ['application/vnd.oasis.opendocument.text']],
        'ods' => [['ods'], ['application/vnd.oasis.opendocument.spreadsheet']],
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
        return self::getAttribute($request, self::KEY);
    }

    /**
     * Add a new format.
     *
     * @param string     $format
     * @param array      $mimeTypes
     * @param array|null $extensions
     *
     * @return self
     */
    public function addFormat($format, array $mimeTypes, array $extensions = null)
    {
        $this->formats[$format] = [is_null($extensions) ? [$format] : $extensions, $mimeTypes];

        return $this;
    }

    /**
     * Set the default format.
     * Set this to null if you want a 406 Not Acceptable response to be generated if no valid format was found.
     *
     * @param string|null $format
     *
     * @return self
     */
    public function defaultFormat($format)
    {
        $this->default = $format;

        if (isset($this->formats[$format])) {
            $item = $this->formats[$format];
            $this->formats = [$format => $item] + $this->formats;
        }

        return $this;
    }

    /**
     * @param array|null $formats Formats which the server supports, in priority order.
     */
    public function __construct($formats = null)
    {
        if (!empty($formats)) {
            $this->formats = $formats;
        }
        $this->default = key($this->formats);
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
        if (empty($format)) {
            //If no valid accept type was found, and no default was specified, then return 406 Not Acceptable.
            $response = $response->withStatus(406);
        } else {
            $contentType = $this->formats[$format][1][0].'; charset=utf-8';

            $response = $next(
                self::setAttribute($request, self::KEY, $format),
                $response->withHeader('Content-Type', $contentType)
            );

            if (!$response->hasHeader('Content-Type')) {
                $response = $response->withHeader('Content-Type', $contentType);
            }
        }

        return $response;
    }

    /**
     * Returns the format using the file extension.
     *
     * @return null|string
     */
    private function getFromExtension(ServerRequestInterface $request)
    {
        $extension = strtolower(pathinfo($request->getUri()->getPath(), PATHINFO_EXTENSION));

        if (empty($extension)) {
            return;
        }

        foreach ($this->formats as $format => $data) {
            if (in_array($extension, $data[0], true)) {
                return $format;
            }
        }
    }

    /**
     * Returns the format using the Accept header.
     *
     * @return null|string
     */
    private function getFromHeader(ServerRequestInterface $request)
    {
        $headers = call_user_func_array('array_merge', array_column($this->formats, 1));
        $mime = $this->negotiateHeader($request->getHeaderLine('Accept'), new Negotiator(), $headers);

        if ($mime !== null) {
            foreach ($this->formats as $format => $data) {
                if (in_array($mime, $data[1], true)) {
                    return $format;
                }
            }
        }
    }
}
