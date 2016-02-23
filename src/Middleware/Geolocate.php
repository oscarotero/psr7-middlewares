<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Geocoder\Geocoder;
use Geocoder\ProviderAggregator;
use Geocoder\Provider\FreeGeoIp;
use Geocoder\Model\AddressCollection;
use Ivory\HttpAdapter\FopenHttpAdapter;
use RuntimeException;

/**
 * Middleware to geolocate the client using the ip.
 */
class Geolocate
{
    const KEY = 'GEOLOCATE';

    /**
     * @var Geocoder
     */
    private $geocoder;

    /**
     * @var bool
     */
    private $saveInSession = false;

    /**
     * Returns the client location.
     *
     * @param ServerRequestInterface $request
     *
     * @return AddressCollection|null
     */
    public static function getLocation(ServerRequestInterface $request)
    {
        return Middleware::getAttribute($request, self::KEY);
    }

    /**
     * Constructor. Set the geocoder instance.
     *
     * @param null|Geocoder $geocoder
     */
    public function __construct(Geocoder $geocoder = null)
    {
        if ($geocoder === null) {
            $geocoder = new ProviderAggregator();
            $geocoder->registerProvider(new FreeGeoIp(new FopenHttpAdapter()));
        }

        $this->geocoder = $geocoder;
    }

    /**
     * Wheter or not save the geolocation in a session variable.
     * 
     * @param bool $save
     * 
     * @return self
     */
    public function saveInSession($save = true)
    {
        $this->saveInSession = $save;

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
        if (!Middleware::hasAttribute($request, ClientIp::KEY)) {
            throw new RuntimeException('Geolocate middleware needs ClientIp executed before');
        }

        if ($this->saveInSession && !Middleware::hasAttribute($request, Middleware::STORAGE_KEY)) {
            throw new RuntimeException('Csrf middleware needs a storage defined');
        }

        $ip = ClientIp::getIp($request);

        if ($ip !== null) {
            if (!$this->saveInSession || ($address = self::fromSession($request, $ip)) === null) {
                $address = $this->geocoder->geocode($ip);
            }

            $request = Middleware::setAttribute($request, self::KEY, $address);

            if ($this->saveInSession) {
                self::toSession($request, $ip, $address);
            }
        }

        return $next($request, $response);
    }

    /**
     * Returns the geolocation from the session storage.
     * 
     * @param ServerRequestInterface $request
     * @param string                 $ip
     * 
     * @return AddressCollection|null
     */
    private static function fromSession(ServerRequestInterface $request, $ip)
    {
        $storage = Middleware::getAttribute($request, Middleware::STORAGE_KEY);
        $ips = $storage->get(self::KEY);

        if (isset($ips[$ip])) {
            return new AddressCollection($ips[$ip]);
        }
    }

    /**
     * Saves the geolocation in the session storage.
     * 
     * @param ServerRequestInterface $request
     * @param string                 $ip
     * @param AddressCollection      $address
     */
    private static function toSession(ServerRequestInterface $request, $ip, AddressCollection $address)
    {
        $storage = Middleware::getAttribute($request, Middleware::STORAGE_KEY);
        $ips = $storage->get(self::KEY) ?: [];
        $ips[$ip] = $address->all();
        $storage->set(self::KEY, $ips);
    }
}
