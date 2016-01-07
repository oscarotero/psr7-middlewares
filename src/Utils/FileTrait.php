<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\RequestInterface;

/**
 * Common methods used by middlewares that read/write files.
 */
trait FileTrait
{
    use BasePathTrait;
    use StorageTrait;

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
        $path = $this->getPath($request->getUri()->getPath());

        $parts = pathinfo($path);
        $path = isset($parts['dirname']) ? $parts['dirname'] : '';
        $filename = isset($parts['basename']) ? $parts['basename'] : '';

        //if it's a directory, append the index file
        if (empty($parts['extension'])) {
            $filename .= "/index.{$indexExt}";
        }

        return Helpers::joinPath($this->storage, $path, $filename);
    }
}
