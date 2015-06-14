<?php
namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware returns the client preferred language
 */
class AcceptLanguage
{
    protected $languages = [];

    /**
     * Creates an instance of this middleware
     *
     * @param null|array $languages
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
        $languages = static::parseLanguagesHeader($request->getHeaderLine('Accept-Language'));

        $request = $request
            ->withAttribute('ACCEPT_LANGUAGE', $languages)
            ->withAttribute('PREFERRED_LANGUAGE', $this->getPreferredLanguage($languages));

        return $next($request, $response);
    }

    /**
     * Get the preferred language
     *
     * @param array $languages
     *
     * @return null|string
     */
    protected function getPreferredLanguage(array $languages)
    {
        $languages = array_keys($languages);

        if (empty($this->languages)) {
            return isset($languages[0]) ? $languages[0] : null;
        }

        if (empty($languages)) {
            return isset($this->languages[0]) ? $this->languages[0] : null;
        }

        $common = array_values(array_intersect($languages, $this->languages));

        return isset($common[0]) ? $common[0] : $this->languages[0];
    }

    /**
     * Parses the Accept-Languages header
     *
     * @param string $header
     *
     * @return array
     */
    protected static function parseLanguagesHeader($header)
    {
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $header, $lang_parse);

        if (count($lang_parse[1])) {
            //create a list like "en" => 0.8
            $langs = array_combine(array_map(function ($lang) {
                return strtolower(substr($lang, 0, 2));
            }, $lang_parse[1]), $lang_parse[4]);

            //set default to 1 for any without q factor
            foreach ($langs as &$val) {
                if ($val === '') {
                    $val = 1;
                } else {
                    $val = floatval($val);
                }
            }

            //sort list based on value
            arsort($langs, SORT_NUMERIC);

            return $langs;
        }

        return [];
    }
}
