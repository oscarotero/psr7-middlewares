<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};

/**
 * Middleware returns the client ip.
 */
class ClientIp
{
    const KEY = 'CLIENT_IPS';

    /**
     * @var bool
     */
    private $remote = false;

    /**
     * @var array The trusted headers
     */
    private $headers = [
        'Forwarded',
        'Forwarded-For',
        'Client-Ip',
        'X-Forwarded',
        'X-Forwarded-For',
        'X-Cluster-Client-Ip',
    ];

    /**
     * Returns all ips found.
     *
     * @param ServerRequestInterface $request
     *
     * @return array|null
     */
    public static function getIps(ServerRequestInterface $request)
    {
        return Middleware::getAttribute($request, self::KEY);
    }

    /**
     * Return the client ip.
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    public static function getIp(ServerRequestInterface $request)
    {
        $ips = self::getIps($request);

        return $ips[0] ?? null;
    }

    /**
     * Constructor. Defines de trusted headers.
     *
     * @param null|array $headers
     */
    public function __construct(array $headers = null)
    {
        if ($headers !== null) {
            $this->headers($headers);
        }
    }

    /**
     * Configure the trusted headers.
     *
     * @param array $headers
     *
     * @return self
     */
    public function headers(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * To get the ip from a remote service.
     * Useful for testing purposes on localhost.
     *
     * @param bool $remote
     *
     * @return self
     */
    public function remote(bool $remote = true): self
    {
        $this->remote = $remote;

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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $request = Middleware::setAttribute($request, self::KEY, $this->scanIps($request));

        return $next($request, $response);
    }

    /**
     * Detect and return all ips found.
     *
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function scanIps(ServerRequestInterface $request): array
    {
        $server = $request->getServerParams();
        $ips = [];

        if ($this->remote) {
            $ips[] = file_get_contents('http://ipecho.net/plain');
        }

        if (!empty($server['REMOTE_ADDR']) && filter_var($server['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            $ips[] = $server['REMOTE_ADDR'];
        }

        foreach ($this->headers as $name) {
            $header = $request->getHeaderLine($name);

            if (!empty($header)) {
                foreach (array_map('trim', explode(',', $header)) as $ip) {
                    if ((array_search($ip, $ips) === false) && filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return $ips;
    }
}
