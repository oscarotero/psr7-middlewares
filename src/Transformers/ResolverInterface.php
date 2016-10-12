<?php

namespace Psr7Middlewares\Transformers;

/**
 * Interface used by all resolvers.
 */
interface ResolverInterface
{
    /**
     * @param string $id
     *
     * @return callable|null
     */
    public function resolve($id);
}
