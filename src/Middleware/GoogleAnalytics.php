<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Utils;

class GoogleAnalytics
{
    use Utils\HtmlInjectorTrait;
    use Utils\AttributeTrait;

    /**
     * @var string The site's ID
     */
    private $siteId;

    /**
     * Constructor. Set the site's ID.
     *
     * @param string $siteId
     */
    public function __construct($siteId)
    {
        $this->siteId = (string) $siteId;
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
        $response = $next($request, $response);

        if (Utils\Helpers::getMimeType($response) === 'text/html' && !Utils\Helpers::isAjax($request)) {
            return $this->inject($response, $this->getCode());
        }

        return $response;
    }

    /**
     * Returns the google code.
     * https://github.com/h5bp/html5-boilerplate/blob/master/src/index.html.
     *
     * @return string
     */
    private function getCode()
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
