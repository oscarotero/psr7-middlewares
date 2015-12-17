<?php

namespace Psr7Middlewares\Utils;

/**
 * Trait used by all middlewares with continueIfNotFound() option.
 */
trait ContinueTrait
{
    private $continue = false;

    /**
     * Configure whether the middleware must continue 
     * or return a 404 response when the content is not found
     * 
     * @param bool $continue
     *
     * @return self
     */
    public function continueIfNotFound($continue = true)
    {
        $this->continue = $continue;

        return $this;
    }
}
