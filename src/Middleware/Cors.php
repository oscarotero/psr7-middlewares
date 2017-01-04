<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\Strategies\SettingsStrategyInterface;
use Neomerx\Cors\Strategies\Settings;

/**
 * Middleware to implement Cors.
 */
class Cors
{
    /**
     * @var SettingsStrategyInterface The settings used by the Analyzer
     */
    private $settings;

    /**
     * @var LoggerInterface|null The logger used by the Analyzer for debugging
     */
    private $logger;

    /**
     * Defines the settings used.
     *
     * @param SettingsStrategyInterface|null $settings
     */
    public function __construct(SettingsStrategyInterface $settings = null)
    {
        $this->settings = $settings ?: new Settings();
    }

    /**
     * Set the server origin.
     *
     * @see Neomerx\Cors\Contracts\Strategies::setServerOrigin
     *
     * @param string|array $origin
     *
     * @return self
     */
    public function origin($origin)
    {
        $this->settings->setServerOrigin($origin);

        return $this;
    }

    /**
     * Set allowed origins.
     *
     * @see Neomerx\Cors\Contracts\Strategies::setRequestAllowedOrigins
     *
     * @param array $origins
     *
     * @return self
     */
    public function allowedOrigins(array $origins)
    {
        $this->settings->setRequestAllowedOrigins($origins);

        return $this;
    }

    /**
     * Set allowed methods.
     *
     * @see Neomerx\Cors\Contracts\Strategies::setRequestAllowedMethods
     * @see Neomerx\Cors\Contracts\Strategies::setForceAddAllowedMethodsToPreFlightResponse
     *
     * @param array $methods
     * @param bool  $force   If allowed methods should be added to pre-flight response
     *
     * @return self
     */
    public function allowedMethods(array $methods, $force = false)
    {
        $this->settings->setRequestAllowedMethods($methods);
        $this->settings->setForceAddAllowedMethodsToPreFlightResponse($force);

        return $this;
    }

    /**
     * Set allowed headers.
     *
     * @see Neomerx\Cors\Contracts\Strategies::setRequestAllowedHeaders
     * @see Neomerx\Cors\Contracts\Strategies::setForceAddAllowedHeadersToPreFlightResponse
     *
     * @param array $headers
     * @param bool  $force   If allowed headers should be added to pre-flight response
     *
     * @return self
     */
    public function allowedHeaders(array $headers, $force = false)
    {
        $this->settings->setRequestAllowedHeaders($headers);
        $this->settings->setForceAddAllowedHeadersToPreFlightResponse($force);

        return $this;
    }

    /**
     * Set headers other than the simple ones that might be exposed to user agent.
     *
     * @see Neomerx\Cors\Contracts\Strategies::setResponseExposedHeaders
     *
     * @param array $headers
     *
     * @return self
     */
    public function exposedHeaders(array $headers)
    {
        $this->settings->setResponseExposedHeaders($headers);

        return $this;
    }

    /**
     * If access with credentials is supported by the resource.
     *
     * @see Neomerx\Cors\Contracts\Strategies::setRequestCredentialsSupported
     *
     * @param bool $allow
     *
     * @return self
     */
    public function allowCredentials($allow = true)
    {
        $this->settings->setRequestCredentialsSupported($allow);

        return $this;
    }

    /**
     * Set pre-flight cache max period in seconds.
     *
     * @see Neomerx\Cors\Contracts\Strategies::setPreFlightCacheMaxAge
     *
     * @param int $maxAge
     *
     * @return self
     */
    public function maxAge($maxAge)
    {
        $this->settings->setPreFlightCacheMaxAge($maxAge);

        return $this;
    }

    /**
     * If request 'Host' header should be checked against server's origin.
     *
     * @see Neomerx\Cors\Contracts\Strategies::setCheckHost
     *
     * @param bool $checkHost
     *
     * @return self
     */
    public function checkHost($checkHost = true)
    {
        $this->settings->setCheckHost($checkHost);

        return $this;
    }

    /**
     * Set the logger used by the Analyzer for debugging purposes.
     *
     * @param LoggerInterface
     *
     * @return self
     */
    public function logger(LoggerInterface $logger)
    {
        $this->logger = $logger;

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
        $analyzer = Analyzer::instance($this->settings);

        if ($this->logger instanceof LoggerInterface) {
            $analyzer->setLogger($this->logger);
        }

        $cors = $analyzer->analyze($request);

        switch ($cors->getRequestType()) {
            case AnalysisResultInterface::ERR_NO_HOST_HEADER:
            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                return $response->withStatus(403);

            case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                return $next($request, $response);

            case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                foreach ($cors->getResponseHeaders() as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }

                return $response->withStatus(200);

            default:
                $response = $next($request, $response);

                foreach ($cors->getResponseHeaders() as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }

                return $response;
        }
    }
}
