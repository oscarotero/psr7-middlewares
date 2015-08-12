<?php
namespace Psr7Middlewares\Utils;

/**
 * Trait used by all middlewares with arguments() option
 */
trait ArgumentsTrait
{
    protected $arguments = [];

    /**
     * Extra arguments passed to the controller
     *
     * @return self
     */
    public function arguments()
    {
        $this->arguments = func_get_args();

        return $this;
    }
}
