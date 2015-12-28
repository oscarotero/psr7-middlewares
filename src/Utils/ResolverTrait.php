<?php

namespace Psr7Middlewares\Utils;

use Psr7Middlewares\Transformers\ResolverInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Trait to provide a resolver to load transformers.
 */
trait ResolverTrait
{
    /**
     * @var ResolverInterface|null
     */
    protected $resolver;

    /**
     * Load the resolver
     *
     * @param ResolverInterface $resolver
     *
     * @return self
     */
    public function resolver(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;

        return $this;
    }
}
