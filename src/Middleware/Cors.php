<?php
namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Neomerx\Cors\Strategies\AnalysisStrategyInterface;

/**
 * Middleware to implement Cors
 */
class Cors
{
    protected $settings;

    /**
     * Constructor. Defines the settings used
     *
     * @param null|AnalysisStrategyInterface $settings
     */
    public function __construct(AnalysisStrategyInterface $settings = null)
    {
        if ($settings !== null) {
            $this->settings($settings);
        }
    }

    /**
     * Set the settings
     *
     * @param AnalysisStrategyInterface $settings
     *
     * @return self
     */
    public function settings(AnalysisStrategyInterface $settings)
    {
        $this->settings = $settings;

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
                break;

            default:
                $response = $next($request, $response);
        }

        $corsHeaders = $cors->getResponseHeaders();

        foreach ($corsHeaders as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
