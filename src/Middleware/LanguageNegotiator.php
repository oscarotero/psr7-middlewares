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
    use Utils\BasePathTrait;
    use Utils\RedirectTrait;

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
        return Middleware::getAttribute($request, self::KEY);
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

        //Use path
        if ($this->usePath) {
            $uri = $request->getUri();
            $path = ltrim($this->getPath($uri->getPath()), '/');

            $dirs = explode('/', $path, 2);
            $first = array_shift($dirs);

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
                $language = isset($this->languages[0]) ? $this->languages[0] : null;
            }

            //Redirect to a path with the language
            if ($this->redirectStatus && $this->usePath) {
                $path = Utils\Helpers::joinPath($this->basePath, $language, $this->getPath($uri->getPath()));

                return self::getRedirectResponse($this->redirectStatus, $uri->withPath($path), $response);
            }
        }

        $request = Middleware::setAttribute($request, self::KEY, $language);

        return $next($request, $response);
    }
}
