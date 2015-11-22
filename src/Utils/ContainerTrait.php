<?php

namespace Psr7Middlewares\Utils;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Trait to provide a container to load parameters.
 */
trait ContainerTrait
{
    /**
     * @var ContainerInterface|null
     */
    protected $container;

    /**
     * @var string|null
     */
    protected $containerId;

    /**
     * Load the container and the key used to get the service.
     *
     * @param ContainerInterface $container
     * @param string             $id
     *
     * @return self
     */
    public function from(ContainerInterface $container, $id)
    {
        $this->container = $container;
        $this->containerId = $id;

        return $this;
    }

    /**
     * Returns the service from the container.
     * 
     * @param string $type
     * @param bool   $required
     *
     * @return null|mixed
     */
    protected function getFromContainer($type = null, $required = true)
    {
        if (isset($this->container)) {
            $item = $this->container->get($this->containerId);

            if ($type !== null && !is_a($item, $type)) {
                throw new InvalidArgumentException("Invalid argument, it's not of type '{$type}'");
            }

            return $item;
        }

        if ($required) {
            $class = get_class($this);

            throw new RuntimeException("Missing required '{$type}' in the middleware '{$class}'");
        }
    }
}
