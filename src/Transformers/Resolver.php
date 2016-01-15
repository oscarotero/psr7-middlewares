<?php

namespace Psr7Middlewares\Transformers;

/**
 * Generic resolver to get transformers.
 */
abstract class Resolver implements ResolverInterface
{
    protected $transformers = [];

    /**
     * Add a new transformer.
     * 
     * @param string   $id
     * @param callable $resolver
     */
    public function add(string $id, callable $resolver)
    {
        $this->transformers[$id] = $resolver;
    }

    /**
     * Resolves the id and returns a transformer or null.
     * 
     * @param string $id
     * 
     * @return callable|null
     */
    public function resolve(string $id)
    {
        if (isset($this->transformers[$id])) {
            return $this->transformers[$id];
        }
    }
}
