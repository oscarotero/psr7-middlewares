<?php
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Relay\RelayBuilder;

abstract class Base extends PHPUnit_Framework_TestCase
{
    protected function request($uri = '', array $headers = array())
    {
        return (new ServerRequest([], [], $uri, null, 'php://temp', $headers))->withUri(new Uri($uri));
    }

    protected function response(array $headers = array())
    {
        return new Response('php://temp', 200, $headers);
    }

    protected function dispatcher(array $middlewares)
    {
        return (new RelayBuilder())->newInstance($middlewares);
    }

    protected function execute(array $middlewares, $url = '', array $headers = array())
    {
        $request = $this->request($url, $headers);
        $response = $this->response();
        $dispatcher = $this->dispatcher($middlewares);

        return $dispatcher($request, $response);
    }
}
