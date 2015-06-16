<?php
namespace Psr7Middlewares\Middleware;

use RuntimeException;
use M6Web\Component\Firewall\Firewall as IpFirewall;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to filter request by ip
 */
class Firewall
{
    protected $trusted;
    protected $untrusted;

    /**
     * Creates an instance of this middleware
     *
     * @param string|array|null $trusted
     * @param string|array|null $untrusted
     * 
     * @return Firewall
     */
    public static function create($trusted = null, $untrusted = null)
    {
        return new static((array) $trusted, (array) $untrusted);
    }

    /**
     * Constructor. Defines de available headers.
     *
     * @param array $trusted   Trusted ips
     * @param array $untrusted Untrusted ips
     */
    public function __construct(array $trusted, array $untrusted)
    {
        $this->trusted = $trusted;
        $this->untrusted = $untrusted;
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
        $ips = $request->getAttribute('CLIENT_IPS');

        if ($ips === null) {
            throw new RuntimeException('Firewall middleware needs ClientIp executed before');
        }

        $firewall = new IpFirewall();

        if ($this->trusted) {
            $firewall->addList($this->trusted, 'trusted', true);
        }

        if ($this->untrusted) {
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
