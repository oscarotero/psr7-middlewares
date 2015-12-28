<?php

namespace Psr7Middlewares\Transformers;

/**
 * Generic resolver to get transformers
 */
abstract class Resolver implements ResolverInterface
{
    /**
     * Resolves the id and returns a transformer or null
     * 
     * @param string $id
     * 
     * @return callable|null
     */
    public function resolve($id)
    {
        if (!empty($id) && $id !== __METHOD__ && method_exists($this, $id)) {
            return [$this, $id];
        }
    }
}
