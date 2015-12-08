<?php

namespace Psr7Middlewares;

interface ResolverInterface
{
    /**
     * @param string $id
     * 
     * @return object
     */
    public function resolve($id);
}
