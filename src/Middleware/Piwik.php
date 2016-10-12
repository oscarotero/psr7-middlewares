<?php

namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr7Middlewares\Utils;

class Piwik
{
    use Utils\HtmlInjectorTrait;
    use Utils\AttributeTrait;

    private $options = [
        ['trackPageView'],
        ['enableLinkTracking'],
    ];

    /**
     * @var int|null The site's ID
     */
    private $siteId = 1;

    /**
     * @var string|null The Piwik url
     */
    private $piwikUrl;

    /**
     * Constructor.Set the Piwik's url.
     *
     * @param string $piwikUrl
     */
    public function __construct($piwikUrl = null)
    {
        if ($piwikUrl !== null) {
            $this->piwikUrl($piwikUrl);
        }
    }

    /**
     * Set the site's id.
     *
     * @param int $siteId
     *
     * @return self
     */
    public function siteId($siteId)
    {
        $this->siteId = $siteId;

        return $this;
    }

    /**
     * Set the piwik url.
     *
     * @param string $url
     *
     * @return self
     */
    public function piwikUrl($url)
    {
        $this->piwikUrl = (string) $url;

        //ensure the url is ending by "/"
        if (substr($this->piwikUrl, -1) !== '/') {
            $this->piwikUrl .= '/';
        }

        return $this;
    }

    /**
     * Add an option.
     *
     * ...
     *
     * @return self
     */
    public function addOption()
    {
        $this->options[] = func_get_args();

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

        if (Utils\Helpers::getMimeType($response) === 'text/html' && !Utils\Helpers::isAjax($request)) {
            return $this->inject($response, $this->getCode());
        }

        return $response;
    }

    /**
     * Returns the piwik code.
     *
     * @return string
     */
    private function getCode()
    {
        $_paq = '';

        foreach ($this->options as $option) {
            $option[0] = "'".$option[0]."'";
            $_paq .= sprintf('_paq.push([%s]);', implode(',', $option));
        }

        return <<<PWK
<script>
    var _paq = _paq || [];
    {$_paq}
    (function() {
        var u="{$this->piwikUrl}";
        _paq.push(['setTrackerUrl', u+'piwik.php']);
        _paq.push(['setSiteId', {$this->siteId}]);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
    })();
</script>
<noscript><p><img src="{$this->piwikUrl}piwik.php?idsite={$this->siteId}" style="border:0;" alt="" /></p></noscript>
PWK;
    }
}
