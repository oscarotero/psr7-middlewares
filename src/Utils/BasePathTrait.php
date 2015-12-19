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
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Removes the basepath from a path.
     *
     * @param string $path
     *
     * @return string
     */
    private function getBasePath($path)
    {
        if ($this->testBasePath($path)) {
            return substr($path, strlen($this->basePath)) ?: '';
        }

        return $path;
    }

    /**
     * Tests the basepath and returns whether the path matches.
     *
     * @param string $path
     *
     * @return bool
     */
    private function testBasePath($path)
    {
        if ($path === '') {
            $path = '/';
        }

        return $this->basePath === '' || strpos($path, $this->basePath) === 0;
    }
}
