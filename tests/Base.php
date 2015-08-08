<?php
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;
use Relay\RelayBuilder;

abstract class Base extends PHPUnit_Framework_TestCase
{
    protected function request($url = '', array $headers = array())
    {
        $request = (new ServerRequest())
            ->withUri(new Uri($url));

        foreach ($headers as $name => $header) {
            $request = $request->withHeader($name, $header);
        }

        return $request;
    }

    protected function response()
    {
        return new Response();
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
