<?php

namespace Psr7Middlewares\Middleware;

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
    use Utils\RedirectTrait;
    use Utils\AttributeTrait;

    const KEY = 'LANGUAGE';

    /**
     * @var array Allowed languages
     */
    private $languages = [];

    /**
     * @var bool Use the path to detect the language
     */
    private $usePath = false;

    /**
     * Returns the language.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    public static function getLanguage(ServerRequestInterface $request)
    {
        return self::getAttribute($request, self::KEY);
    }

    /**
     * Define de available languages.
     *
     * @param array $languages
     */
    public function __construct(array $languages)
    {
        $this->languages = $languages;
    }

    /**
     * Use the base path to detect the current language.
     *
     * @param bool $usePath
     *
     * @return self
     */
    public function usePath($usePath = true)
    {
        $this->usePath = $usePath;

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
        $language = null;
        $uri = $request->getUri();

        //Use path
        if ($this->usePath) {
            $path = ltrim($uri->getPath(), '/');
            $dirs = explode('/', $path, 2);
            $first = strtolower(array_shift($dirs));

            if (!empty($first) && in_array($first, $this->languages, true)) {
                $language = $first;

                //remove the language in the path
                $request = $request->withUri($uri->withPath('/'.array_shift($dirs)));
            }
        }

        //Use http headers
        if ($language === null) {
            $language = $this->negotiateHeader($request->getHeaderLine('Accept-Language'), new Negotiator(), $this->languages);

            if (empty($language)) {
                $language = isset($this->languages[0]) ? $this->languages[0] : '';
            }

            //Redirect to a path with the language
            if ($this->redirectStatus !== false && $this->usePath) {
                $path = Utils\Helpers::joinPath($language, $uri->getPath());

                return $this->getRedirectResponse($request, $uri->withPath($path), $response);
            }
        }

        $response = $next(
            self::setAttribute($request, self::KEY, $language),
            $response->withHeader('Content-Language', $language)
        );

        if (!$response->hasHeader('Content-Language')) {
            $response = $response->withHeader('Content-Language', $language);
        }

        return $response;
    }
}
