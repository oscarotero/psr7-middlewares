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
    protected $formats = [
        'atom' => ['application/atom+xml'],
        'css' => ['text/css'],
        'html' => ['text/html', 'application/xhtml+xml'],
        'gif' => ['image/gif'],
        'jpg' => ['image/jpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg', 'image/jpg'],
        'js'  => ['text/javascript', 'application/javascript', 'application/x-javascript'],
        'jsonp'  => ['text/javascript', 'application/javascript', 'application/x-javascript'],
        'json' => ['application/json', 'text/json', 'application/x-json'],
        'png' => ['image/png', 'image/x-png'],
        'pdf' => ['application/pdf', 'application/x-download'],
        'rdf' => ['application/rdf+xml'],
        'rss' => ['application/rss+xml'],
        'txt' => ['text/plain'],
        'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
        'zip' => ['application/zip', 'application/x-zip', 'application/x-zip-compressed'],
    ];

    /**
     * Constructor. Defines de available formats.
     *
     * @param Negotiator $negotiator
     */
    public function __construct(Negotiator $negotiator = null)
    {
        if ($negotiator !== null) {
            $this->negotiator($negotiator);
        }
    }

    /**
     * Set the negotiator used
     *
     * @param Negotiator $negotiator
     *
     * @return self
     */
    public function negotiator(Negotiator $negotiator)
    {
        $this->negotiator = $negotiator;

        return $this;
    }

    /**
     * Add a new format
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
        $negotiator = $this->getNegotiator();

        if (!$negotiator->normalizePriorities([$format])) {
            $format = $negotiator->getBestFormat($request->getHeaderLine('Accept'));
        }

        //Save the format as attribute
        $request = $request->withAttribute('FORMAT', $format);

        //Set the content-type to the response
        if (($mime = $negotiator->normalizePriorities([$format]))) {
            return $next($request, $response->withHeader('Content-Type', $mime[0].'; charset=utf-8'));
        }

        return $next($request, $response);
    }

    /**
     * Returns the negotiator
     *
     * @return Negotiator
     */
    protected function getNegotiator()
    {
        if ($this->negotiator === null) {
            $this->negotiator = new Negotiator();

            foreach ($this->formats as $name => $mimeTypes) {
                $this->negotiator->registerFormat($name, $mimeTypes, true);
            }
        }

        return $this->negotiator;
    }
}
