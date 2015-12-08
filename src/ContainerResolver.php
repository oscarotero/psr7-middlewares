<?php

namespace Psr7Middlewares;

use Interop\Container\ContainerInterface;

/**
 * Adapter to use container-interop as resolver.
 */
class ContainerResolver implements ResolverInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($id)
    {
        return $this->container->get($id);
    }
}
