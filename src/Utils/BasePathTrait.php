<?php
namespace Psr7Middlewares\Utils;

/**
 * Utilities used by middlewares with basePath options
 */
trait BasePathTrait
{
    protected $basePath = '';

    /**
     * Set the basepath used in the request
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
     * Removes the basepath from a path
     *
     * @param string $path
     *
     * @return string
     */
    protected function getBasePath($path)
    {
        if (!empty($this->basePath) && strpos($path, $this->basePath) === 0) {
            return substr($path, strlen($this->basePath)) ?: '';
        }

        return $path;
    }
}
