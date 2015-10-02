<?php
namespace Psr7Middlewares\Utils;

use Psr7Middlewares\Middleware\FormatNegotiator;

/**
 * Common methods used by middlewares that read/write files
 */
trait FileTrait
{
    use BasePathTrait;
    use StorageTrait;

    /**
     * Returns the filename of the response file
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    protected function getFilename(ServerRequestInterface $request)
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

            $filename = 'index.'.(FormatNegotiator::getFormat($request) ?: 'html');
        }

        return "{$this->storage}{$path}/{$filename}";
    }
}
