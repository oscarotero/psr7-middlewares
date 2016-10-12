<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\RequestInterface;

/**
 * Common methods used by middlewares that read/write files.
 */
trait FileTrait
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var bool
     */
    private $appendQuery;

    /**
     * Set the storage directory of the file.
     *
     * @param string $directory
     */
    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Set whether use or not the uri query to generate the filenames.
     *
     * @param bool $appendQuery
     *
     * @return self
     */
    public function appendQuery($appendQuery = true)
    {
        $this->appendQuery = $appendQuery;

        return $this;
    }

    /**
     * Returns the filename of the response file.
     *
     * @param RequestInterface $request
     * @param string           $indexExt
     *
     * @return string
     */
    private function getFilename(RequestInterface $request, $indexExt = 'html')
    {
        $path = urldecode($request->getUri()->getPath());
        $parts = pathinfo($path);
        $path = isset($parts['dirname']) ? $parts['dirname'] : '';
        $filename = isset($parts['basename']) ? $parts['basename'] : '';

        //if it's a directory, append the index file
        if (empty($parts['extension'])) {
            $filename .= "/index.{$indexExt}";
        }

        if ($this->appendQuery && $request->getUri()->getQuery()) {
            $filename .= '?'.urlencode($request->getUri()->getQuery());
        }

        return Helpers::joinPath($this->directory, $path, $filename);
    }
}
