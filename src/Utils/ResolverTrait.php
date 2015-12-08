<?php

namespace Psr7Middlewares\Utils;

use Psr7Middlewares\ResolverInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Trait to provide a resolver to load parameters.
 */
trait ResolverTrait
{
    /**
     * @var ResolverInterface|null
     */
    protected $resolver;

    /**
     * @var string|null
     */
    protected $resolverId;

    /**
     * Load the resolver and the key used to get the object.
     *
     * @param ResolverInterface $resolver
     * @param string            $id
     *
     * @return self
     */
    public function from(ResolverInterface $resolver, $id)
    {
        $this->resolver = $resolver;
        $this->resolverId = $id;

        return $this;
    }

    /**
     * Returns the object from the resolver.
     * 
     * @param string $type
     * @param bool   $required
     *
     * @return null|mixed
     */
    protected function getFromResolver($type = null, $required = true)
    {
        if (isset($this->resolver)) {
            $item = $this->resolver->resolve($this->resolverId);

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
