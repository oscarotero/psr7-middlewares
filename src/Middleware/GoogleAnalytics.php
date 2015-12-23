<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Utils;

class GoogleAnalytics
{
    use Utils\HtmlInjectorTrait;

    /**
     * @var string|null The site's ID
     */
    private $id;

    /**
     * Constructor.Set the site's ID.
     *
     * @param string $id
     */
    public function __construct($id = null)
    {
        if ($id !== null) {
            $this->id($id);
        }
    }

    /**
     * Set the site's id
     *
     * @param string $id
     *
     * @return self
     */
    public function id($id)
    {
        $this->id = (string) $id;

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
        $response = $next($request, $response);

        if ($this->id && $this->isInjectable($request)) {
            return $this->inject($response, self::getCode($this->id));
        }

        return $response;
    }

    /**
     * Returns the google code.
     * https://github.com/h5bp/html5-boilerplate/blob/master/src/index.html
     * 
     * @param string $id
     * 
     * @return string
     */
    private static function getCode($id)
    {
        return <<<GA
<script>
    (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
    function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
    e=o.createElement(i);r=o.getElementsByTagName(i)[0];
    e.src='https://www.google-analytics.com/analytics.js';
    r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
    ga('create','{$id}','auto');ga('send','pageview');
</script>
GA;
    }
}
