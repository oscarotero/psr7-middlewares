<?php
namespace Psr7Middlewares\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Middleware to save the response into a file
 */
class SaveResponse
{
    protected $documentRoot;

    /**
     * Creates an instance of this middleware
     *
     * @param string $documentRoot
     *
     * @return SaveResponse
     */
    public static function create($documentRoot = '')
    {
        return new static($documentRoot);
    }

    /**
     * Constructor. Set the document root
     *
     * @param string $documentRoot
     */
    public function __construct($documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }

    /**
     * Execute the middleware
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!$this->mustWrite($request, $response)) {
            return $next($request, $response);
        }

        $file = $this->documentRoot.$request->getUri()->getPath();

        $parts = pathinfo($request->getUri()->getPath());
        $path = '/'.(isset($parts['dirname']) ? $parts['dirname'] : '');
        $filename = isset($parts['basename']) ? $parts['basename'] : '';

        //if it's a directory, append "/index.html"
        if (empty($parts['extension'])) {
            if ($path === '/') {
                $path .= $filename;
            } else {
                $path .= '/'.$filename;
            }

            $filename = 'index.'.($request->getAttribute('FORMAT') ?: 'html');
        }

        $this->writeFile($response->getBody(), $this->documentRoot.$path.'/'.$filename);

        return $next($request, $response);
    }

    /**
     * Check whether the response has to be written or not
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return boolean
     */
    protected function mustWrite(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        //Do not save requests with query parameters
        if (count($request->getQueryParams())) {
            return false;
        }

        return true;
    }

    /**
     * Write the stream to given path
     *
     * @param StreamInterface $stream
     * @param string          $path
     */
    private function writeFile(StreamInterface $stream, $path)
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $handle = fopen($path, 'wb+');

        if (false === $handle) {
            throw new RuntimeException('Unable to write to designated path');
        }

        $stream->rewind();

        while (!$stream->eof()) {
            fwrite($handle, $stream->read(4096));
        }

        fclose($handle);
    }
}
