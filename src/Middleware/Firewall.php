<?php

namespace Psr7Middlewares\Middleware;

use RuntimeException;
use Psr7Middlewares\Utils;
use M6Web\Component\Firewall\Firewall as IpFirewall;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to filter request by ip.
 */
class Firewall
{
    use Utils\AttributeTrait;

    /**
     * @var array|null Trusted ips
     */
    private $trusted;

    /**
     * @var array|null Untrusted ips
     */
    private $untrusted;

    /**
     * Constructor. Set the trusted ips.
     *
     * @param array|null $trusted
     */
    public function __construct(array $trusted = null)
    {
        if ($trusted !== null) {
            $this->trusted($trusted);
        }
    }

    /**
     * Set trusted ips.
     *
     * @return self
     */
    public function trusted(array $trusted)
    {
        $this->trusted = $trusted;

        return $this;
    }

    /**
     * Set untrusted ips.
     *
     * @return self
     */
    public function untrusted(array $untrusted)
    {
        $this->untrusted = $untrusted;

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
            throw new RuntimeException('Firewall middleware needs ClientIp executed before');
        }

        $ips = ClientIp::getIps($request) ?: [];
        $firewall = new IpFirewall();

        if (!empty($this->trusted)) {
            $firewall->addList($this->trusted, 'trusted', true);
        }

        if (!empty($this->untrusted)) {
            $firewall->addList($this->untrusted, 'untrusted', false);
        }

        foreach ($ips as $ip) {
            $ok = $firewall->setIpAddress($ip)->handle();

            if (!$ok) {
                return $response->withStatus(403);
            }
        }

        return $next($request, $response);
    }
}
