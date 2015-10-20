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
     *
     * @return string
     */
    protected function getFilename(RequestInterface $request)
    {
        $path = $this->getBasePath($request->getUri()->getPath());

        $parts = pathinfo($path);
        $path = '/'.(isset($parts['dirname']) ? $parts['dirname'] : '');
        $filename = isset($parts['basename']) ? $parts['basename'] : '';

        //if it's a directory, append "/index.html"
        if (empty($parts['extension'])) {
            if ($path === '/') {
                $path .= $filename;
            } else {
                $path .= "/{$filename}";
            }

            $extension = strtolower(pathinfo($request->getUri()->getPath(), PATHINFO_EXTENSION)) ?: 'html';
            $filename = "index.{$extension}";
        }

        return "{$this->storage}{$path}/{$filename}";
    }
}
