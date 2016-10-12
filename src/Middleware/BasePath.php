<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to strip the path prefix.
 */
class BasePath
{
    const KEY = 'BASE_PATH';
    const KEY_GENERATOR = 'BASE_PATH_GENERATOR';

    use Utils\BasePathTrait;
    use Utils\AttributeTrait;

    /**
     * @var bool
     */
    private $autodetect = false;

    /**
     * Returns the basePath.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    public static function getBasePath(ServerRequestInterface $request)
    {
        return self::getAttribute($request, self::KEY);
    }

    /**
     * Returns a callable to build a full path.
     *
     * @param ServerRequestInterface $request
     *
     * @return callable|null
     */
    public static function getGenerator(ServerRequestInterface $request)
    {
        return self::getAttribute($request, self::KEY_GENERATOR);
    }

    /**
     * Constructor. Set the path prefix.
     *
     * @param string|null $basePath
     */
    public function __construct($basePath = null)
    {
        if ($basePath !== null) {
            $this->basePath($basePath);
        }
    }

    /**
     * Autodetect the basePath.
     *
     * @param bool $autodetect
     *
     * @return self
     */
    public function autodetect($autodetect = true)
    {
        $this->autodetect = $autodetect;

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
        if ($this->autodetect) {
            $this->basePath(Utils\Helpers::joinPath(self::detectBasePath($request), $this->basePath));
        }

        $uri = $request->getUri();
        $path = $this->getPath($uri->getPath());
        $request = $request->withUri($uri->withPath($path));

        $generator = function ($path) {
            return Utils\Helpers::joinPath($this->basePath, $path);
        };

        $request = self::setAttribute($request, self::KEY, $this->basePath);
        $request = self::setAttribute($request, self::KEY_GENERATOR, $generator);

        return $next($request, $response);
    }

    /**
     * Auto-detect the base path from the request environment.
     *
     * Uses a variety of criteria in order to detect the base URL of the request
     * (i.e., anything additional to the document root).
     *
     * This code has been adapted from the Zend implementation:
     * https://github.com/zendframework/zend-http/blob/master/src/PhpEnvironment/Request.php
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private static function detectBasePath(ServerRequestInterface $request)
    {
        $server = $request->getServerParams();

        $filename = isset($server['SCRIPT_FILENAME']) ? $server['SCRIPT_FILENAME'] : '';
        $scriptName = isset($server['SCRIPT_NAME']) ? $server['SCRIPT_NAME'] : null;
        $phpSelf = isset($server['PHP_SELF']) ? $server['PHP_SELF'] : null;
        $origScriptName = isset($server['ORIG_SCRIPT_NAME']) ? $server['ORIG_SCRIPT_NAME'] : null;

        if ($scriptName !== null && basename($scriptName) === $filename) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $filename) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $filename) {
            // 1and1 shared hosting compatibility.
            $baseUrl = $origScriptName;
        } else {
            // Backtrack up the SCRIPT_FILENAME to find the portion
            // matching PHP_SELF.
            $baseUrl = '/';
            $basename = basename($filename);

            if ($basename) {
                $path = ($phpSelf ? trim($phpSelf, '/') : '');
                $basePos = strpos($path, $basename) ?: 0;
                $baseUrl .= substr($path, 0, $basePos).$basename;
            }
        }

        // If the baseUrl is empty, then simply return it.
        if (empty($baseUrl)) {
            return '';
        }

        // Does the base URL have anything in common with the request URI?
        $requestUri = $request->getUri()->getPath();

        // Full base URL matches.
        if (0 === strpos($requestUri, $baseUrl)) {
            return $baseUrl;
        }

        // Directory portion of base path matches.
        $baseDir = str_replace('\\', '/', dirname($baseUrl));

        if (0 === strpos($requestUri, $baseDir)) {
            return $baseDir;
        }

        $truncatedRequestUri = $requestUri;

        if (false !== ($pos = strpos($requestUri, '?'))) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);

        // No match whatsoever
        if (empty($basename) || false === strpos($truncatedRequestUri, $basename)) {
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of the base path. $pos !== 0 makes sure it is not matching a
        // value from PATH_INFO or QUERY_STRING.
        if (strlen($requestUri) >= strlen($baseUrl)
            && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)
        ) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }
}
