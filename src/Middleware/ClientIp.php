<?php
namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware returns the client ip
 */
class ClientIp
{
    protected $headers = [
        'Client-Ip',
        'X-Forwarded-For',
        'X-Forwarded',
        'X-Cluster-Client-Ip',
        'Forwarded-For',
        'Forwarded',
    ];

    /**
     * Constructor. Defines de available headers.
     *
     * @param null|array $headers
     */
    public function __construct(array $headers = null)
    {
        if ($headers !== null) {
            $this->headers = $headers;
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
        $ips = $this->getIps($request);

        $request = $request
            ->withAttribute('CLIENT_IPS', $ips)
            ->withAttribute('CLIENT_IP', isset($ips[0]) ? $ips[0] : null);

        return $next($request, $response);
    }

    /**
     * Detect and return all ips found.
     *
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    protected function getIps(ServerRequestInterface $request)
    {
        $ips = [];

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
