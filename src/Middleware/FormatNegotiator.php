<?php
namespace Psr7Middlewares\Middleware;

use Negotiation\FormatNegotiator as Negotiator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware returns the client preferred format
 */
class FormatNegotiator
{
    protected $negotiator;

    protected static $formats = [
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

    /**
     * Creates an instance of this middleware
     *
     * @param Negotiator|null $negotiator
     *
     * @return FormatNegotiator
     */
    public static function create(Negotiator $negotiator = null)
    {
        if ($negotiator === null) {
            $negotiator = new Negotiator();

            foreach (static::$formats as $name => $mimeTypes) {
                $negotiator->registerFormat($name, $mimeTypes, true);
            }
        }

        return new static($negotiator);
    }

    /**
     * Constructor. Defines de available formats.
     *
     * @param Negotiator $negotiator
     */
    public function __construct(Negotiator $negotiator)
    {
        $this->negotiator = $negotiator;
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
        //Calculate using the extension
        $format = strtolower(pathinfo($request->getUri()->getPath(), PATHINFO_EXTENSION));

        //Calculate using the header
        if (!$this->negotiator->normalizePriorities([$format])) {
            $format = $this->negotiator->getBestFormat($request->getHeaderLine('Accept'));
        }

        //Save the format as attribute
        $request = $request->withAttribute('FORMAT', $format);

        //Set the content-type to the response
        if (($mime = $this->negotiator->normalizePriorities([$format]))) {
            return $next($request, $response->withHeader('Content-Type', $mime[0].'; charset=utf-8'));
        }

        return $next($request, $response);
    }
}
