<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\RequestInterface;

/**
 * Common methods used by middlewares that read/write files.
 */
trait FileTrait
{
    use BasePathTrait;

    /**
     * @var string
     */
    private $directory;

    /**
     * Set the storage directory of the file.
     *
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * Returns the filename of the response file.
     *
     * @param RequestInterface $request
     * @param string           $indexExt
     *
     * @return string
     */
    private function getFilename(RequestInterface $request, string $indexExt = 'html'): string
    {
        $path = $this->getPath($request->getUri()->getPath());

        $parts = pathinfo($path);
        $path = $parts['dirname'] ?? '';
        $filename = $parts['basename'] ?? '';

        //if it's a directory, append the index file
        if (empty($parts['extension'])) {
            $filename .= "/index.{$indexExt}";
        }

        return Helpers::joinPath($this->directory, $path, $filename);
    }
}
