<?php
namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware returns the client ip
 */
class ClientIp
{
    const KEY = 'CLIENT_IPS';

    protected $headers = [
        'Forwarded',
        'Forwarded-For',
        'Client-Ip',
        'X-Forwarded',
        'X-Forwarded-For',
        'X-Cluster-Client-Ip',
    ];

    /**
     * Returns all ips found
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
     * Return the client ip
     *
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    public static function getIp(ServerRequestInterface $request)
    {
        $ips = static::getIps($request);

        return isset($ips[0]) ? $ips[0] : null;
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
     * Configure the trusted headers
     *
     * @param array $headers
     *
     * @return self
     */
    public function headers(array $headers)
    {
        $this->headers = $headers;

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
    protected function scanIps(ServerRequestInterface $request)
    {
        $server = $request->getServerParams();
        $ips = [];

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
