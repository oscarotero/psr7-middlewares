<?php declare(strict_types=1);

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\{Middleware, Utils};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use RuntimeException;

class GoogleAnalytics
{
    use Utils\HtmlInjectorTrait;

    /**
     * @var string The site's ID
     */
    private $siteId;

    /**
     * Constructor. Set the site's ID.
     *
     * @param string $siteId
     */
    public function __construct(string $siteId)
    {
        $this->siteId = $siteId;
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
        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('The GoogleAnalytics middleware needs FormatNegotiator executed before');
        }

        if (FormatNegotiator::getFormat($request) === 'html' && !Utils\Helpers::isAjax($request)) {
            $response = $this->inject($response, $this->getCode());
        }

        return $next($request, $response);
    }

    /**
     * Returns the google code.
     * https://github.com/h5bp/html5-boilerplate/blob/master/src/index.html.
     * 
     * @return string
     */
    private function getCode(): string
    {
        return <<<GA
<script>
    (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
    function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
    e=o.createElement(i);r=o.getElementsByTagName(i)[0];
    e.src='https://www.google-analytics.com/analytics.js';
    r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
    ga('create','{$this->siteId}','auto');ga('send','pageview');
</script>
GA;
    }
}
