<?php

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;
use Relay\RelayBuilder;

abstract class Base extends PHPUnit_Framework_TestCase
{
    /**
     * @param string $uri
     * @param array  $headers
     * @param array  $server
     *
     * @return ServerRequest
     */
    protected function request($uri = '', array $headers = array(), array $server = array())
    {
        return (new ServerRequest($server, [], $uri, null, 'php://temp', $headers))->withUri(new Uri($uri));
    }

    /**
     * @param array $headers
     *
     * @return Response
     */
    protected function response(array $headers = array())
    {
        return new Response('php://temp', 200, $headers);
    }

    /**
     * @param string $content
     *
     * @return Stream
     */
    protected function stream($content = '')
    {
        $stream = new Stream('php://temp', 'r+');

        if ($content) {
            $stream->write($content);
        }

        return $stream;
    }

    /**
     * @param callable[]    $middlewares
     * @param ServerRequest $request
     * @param Response      $response
     *
     * @return ResponseInterface
     */
    protected function dispatch(array $middlewares, ServerRequest $request, Response $response)
    {
        $dispatcher = (new RelayBuilder())->newInstance($middlewares);

        return $dispatcher($request, $response);
    }

    /**
     * @param callable[] $middlewares
     * @param string     $url
     * @param array      $headers
     *
     * @return ResponseInterface
     */
    protected function execute(array $middlewares, $url = '', array $headers = array())
    {
        $request = $this->request($url, $headers);
        $response = $this->response();

        return $this->dispatch($middlewares, $request, $response);
    }
}
