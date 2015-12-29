<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils;
use Negotiation\LanguageNegotiator as Negotiator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware returns the client preferred language.
 */
class LanguageNegotiator
{
    use Utils\NegotiateTrait;

    const KEY = 'LANGUAGE';

    /**
     * @var array Allowed languages
     */
    private $languages = [];

    /**
     * Returns the language.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    public static function getLanguage(ServerRequestInterface $request)
    {
        return Middleware::getAttribute($request, self::KEY);
    }

    /**
     * Constructor. Defines de available languages.
     *
     * @param array $languages
     */
    public function __construct(array $languages = null)
    {
        if ($languages !== null) {
            $this->languages($languages);
        }
    }

    /**
     * Configure the available languages.
     *
     * @param array $languages
     *
     * @return self
     */
    public function languages(array $languages)
    {
        $this->languages = $languages;

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
        $language = $this->negotiateHeader($request->getHeaderLine('Accept-Language'), new Negotiator(), $this->languages);

        if (empty($language)) {
            $language = isset($this->languages[0]) ? $this->languages[0] : null;
        }

        $request = Middleware::setAttribute($request, self::KEY, $language);

        return $next($request, $response);
    }
}
