<?php

namespace Psr7Middlewares\Utils;

/**
 * Utilities used by middlewares with basePath options.
 */
trait BasePathTrait
{
    private $basePath = '';

    /**
     * Set the basepath used in the request.
     *
     * @param string $basePath
     *
     * @return self
     */
    public function basePath($basePath)
    {
        if (strlen($basePath) > 1 && substr($basePath, -1) === '/') {
            $this->basePath = substr($basePath, 0, -1);
        } else {
            $this->basePath = $basePath;
        }

        return $this;
    }

    /**
     * Removes the basepath from a path.
     *
     * @param string $path
     *
     * @return string
     */
    private function getPath($path)
    {
        if ($this->basePath === '' || strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath)) ?: '';
        }

        return $path === '' ? '/' : $path;
    }
}
