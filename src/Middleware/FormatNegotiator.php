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
    protected $formats = [];

    /**
     * Creates an instance of this middleware
     *
     * @param null|array $formats
     * 
     * @return FormatNegotiator
     */
    public static function create(array $formats = null)
    {
        return new static($formats);
    }

    /**
     * Constructor. Defines de available formats.
     *
     * @param null|array $formats
     */
    public function __construct(array $formats = null)
    {
        if ($formats !== null) {
            $this->formats = $formats;
        }
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
        $negotiator = new Negotiator();

        //Calculate using the extension
        $format = strtolower(pathinfo($request->getUri()->getPath(), PATHINFO_EXTENSION));

        if ($negotiator->normalizePriorities([$format])) {
            return $next($request->withAttribute('FORMAT', $format), $response);
        }

        //Calculate using the header
        $format = $negotiator->getBestFormat($request->getHeaderLine('Accept'), $this->formats);

        return $next($request->withAttribute('FORMAT', $format), $response);
    }
}
