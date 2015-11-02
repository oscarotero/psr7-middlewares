<?php

namespace Psr7Middlewares\Middleware;

use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\Strategies\SettingsStrategyInterface;

/**
 * Middleware to implement Cors.
 */
class Cors
{
    /**
     * @var SettingsStrategyInterface|null The settings used by the Analyzer
     */
    protected $settings;

    /**
     * Constructor. Defines the settings used.
     *
     * @param null|SettingsStrategyInterface $settings
     */
    public function __construct(SettingsStrategyInterface $settings = null)
    {
        if ($settings !== null) {
            $this->settings($settings);
        }
    }

    /**
     * Set the settings.
     *
     * @param SettingsStrategyInterface $settings
     *
     * @return self
     */
    public function settings(SettingsStrategyInterface $settings)
    {
        $this->settings = $settings;

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
        if ($this->settings === null) {
            throw new RuntimeException('No settings provided for Cors middleware');
        }

        $cors = Analyzer::instance($this->settings)->analyze($request);

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
