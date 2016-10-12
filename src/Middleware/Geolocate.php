<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
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
    use Utils\StorageTrait;

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
        return self::getAttribute($request, self::KEY);
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
        if (!self::hasAttribute($request, ClientIp::KEY)) {
            throw new RuntimeException('Geolocate middleware needs ClientIp executed before');
        }

        $ip = ClientIp::getIp($request);

        if ($ip !== null) {
            if ($this->saveInSession) {
                $ips = &self::getStorage($request, self::KEY);

                if (isset($ips[$ip])) {
                    $address = new AddressCollection($ips[$ip]);
                } else {
                    $address = $this->geocoder->geocode($ip);
                    $ips[$ip] = $address->all();
                }
            } else {
                $address = $this->geocoder->geocode($ip);
            }

            $request = self::setAttribute($request, self::KEY, $address);
        }

        return $next($request, $response);
    }
}
