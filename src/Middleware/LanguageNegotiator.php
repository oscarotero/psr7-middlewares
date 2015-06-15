<?php
namespace Psr7Middlewares\Middleware;

use Negotiation\LanguageNegotiator as Negotiator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware returns the client preferred language
 */
class LanguageNegotiator
{
    protected $languages = [];

    /**
     * Creates an instance of this middleware
     *
     * @param null|array $languages
     * 
     * @return LanguageNegotiator
     */
    public static function create(array $languages = null)
    {
        return new static($languages);
    }

    /**
     * Constructor. Defines de available languages.
     *
     * @param null|array $languages
     */
    public function __construct(array $languages = null)
    {
        if ($languages !== null) {
            $this->languages = $languages;
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
        $language = $negotiator->getBest($request->getHeaderLine('Accept-Language'), $this->languages);

        if ($language) {
            $language = strtolower(substr($language->getValue(), 0, 2));
        } else {
            $language = isset($this->languages[0]) ? $this->languages[0] : null;
        }

        return $next($request->withAttribute('LANGUAGE', $language), $response);
    }
}
