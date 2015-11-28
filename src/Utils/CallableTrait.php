<?php

namespace Psr7Middlewares\Utils;

use RuntimeException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Utilities used by middlewares with callables.
 */
trait CallableTrait
{
    use ArgumentsTrait;

    /**
     * Execute the callable.
     *
     * @param mixed             $target
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    protected function executeCallable($target, RequestInterface $request, ResponseInterface $response)
    {
        try {
            ob_start();

            $arguments = array_merge([$request, $response], $this->arguments);
            $target = static::getCallable($target, $arguments);
            $return = call_user_func_array($target, $arguments);

            if ($return instanceof ResponseInterface) {
                $response = $return;
                $return = '';
            }

            $return = ob_get_contents().$return;
            $body = $response->getBody();

            if ($return !== '' && $body->isWritable()) {
                $body->write($return);
            }

            return $response;
        } catch (\Exception $exception) {
            throw $exception;
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    /**
     * Resolves the target of the route and returns a callable.
     *
     * @param mixed $target
     * @param array $construct_args
     *
     * @throws RuntimeException If the target is not callable
     *
     * @return callable
     */
    protected static function getCallable($target, array $construct_args)
    {
        //if it's callable as is
        if (is_callable($target)) {
            return $target;
        }

        if (is_string($target)) {
            //is a class "classname::method"
            if (strpos($target, '::') === false) {
                $class = $target;
                $method = '__invoke';
            } else {
                list($class, $method) = explode('::', $target, 2);
            }

            if (!class_exists($class)) {
                throw new RuntimeException("The class {$class} does not exists");
            }

            $class = new \ReflectionClass($class);
            $instance = $class->hasMethod('__construct') ? $class->newInstanceArgs($construct_args) : $class->newInstance();
            $target = [$instance, $method];
        }

        throw new RuntimeException('The route target is not callable');
    }
}
