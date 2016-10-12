<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class AccessLog
{
    use Utils\AttributeTrait;

    /**
     * @var LoggerInterface The router container
     */
    private $logger;

    /**
     * @var bool
     */
    private $combined = false;

    /**
     * Set the LoggerInterface instance.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Whether use the combined log format instead the common log format.
     *
     * @param bool $combined
     *
     * @return self
     */
    public function combined($combined = true)
    {
        $this->combined = $combined;

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
        if (!self::hasAttribute($request, ClientIp::KEY)) {
            throw new RuntimeException('AccessLog middleware needs ClientIp executed before');
        }

        $response = $next($request, $response);
        $message = $this->combined ? self::combinedFormat($request, $response) : self::commonFormat($request, $response);

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            $this->logger->error($message);
        } else {
            $this->logger->info($message);
        }

        return $response;
    }

    /**
     * Generates a message using the Apache's Common Log format
     * https://httpd.apache.org/docs/2.4/logs.html#accesslog.
     *
     * Note: The user identifier (identd) is ommited intentionally
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return string
     */
    private static function commonFormat(ServerRequestInterface $request, ResponseInterface $response)
    {
        return sprintf('%s %s [%s] "%s %s %s/%s" %d %d',
            ClientIp::getIp($request),
            $request->getUri()->getUserInfo() ?: '-',
            strftime('%d/%b/%Y:%H:%M:%S %z'),
            strtoupper($request->getMethod()),
            $request->getUri()->getPath(),
            strtoupper($request->getUri()->getScheme()),
            $request->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getBody()->getSize()
        );
    }

    /**
     * Generates a message using the Apache's Combined Log format
     * This is exactly the same than Common Log, with the addition of two more fields: Referer and User-Agent headers.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return string
     */
    private static function combinedFormat(ServerRequestInterface $request, ResponseInterface $response)
    {
        return sprintf('%s "%s" "%s"',
            self::commonFormat($request, $response),
            $request->getHeaderLine('Referer'),
            $request->getHeaderLine('User-Agent')
        );
    }
}
