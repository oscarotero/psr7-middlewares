<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
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
    use Utils\ResolverTrait;

    const KEY = 'GEOLOCATE';

    /**
     * @var Geocoder
     */
    protected $geocoder;

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
        if ($geocoder !== null) {
            $this->geocoder($geocoder);
        }
    }

    /**
     * Set a geocoder instance.
     * 
     * @param Geocoder $geocoder
     * 
     * @return self
     */
    public function geocoder(Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;

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

        $geocoder = $this->geocoder ?: $this->getFromResolver(Geocoder::CLASS, false) ?: $this->getGeocoder();
        $ip = ClientIp::getIp($request);

        if ($ip) {
            $ip = '123.9.34.23';
            $request = Middleware::setAttribute($request, self::KEY, $geocoder->geocode($ip));
        }

        return $next($request, $response);
    }

    /**
     * Create a default geocoder instance.
     * 
     * @return Geocoder
     */
    protected function getGeocoder()
    {
        $geocoder = new ProviderAggregator();
        $httpAdapter = new FopenHttpAdapter();
        $geocoder->registerProvider(new FreeGeoIp($httpAdapter));

        return $geocoder;
    }
}
