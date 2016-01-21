<?php

namespace Psr7Middlewares\Utils;

use RuntimeException;
use Psr7Middlewares\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Utilities used by middlewares with callables.
 */
trait CallableTrait
{
    private $arguments = [];

    /**
     * Extra arguments passed to the callable.
     *
     * @return self
     */
    public function arguments()
    {
        $this->arguments = func_get_args();

        return $this;
    }

    /**
     * Execute the callable.
     *
     * @param mixed             $target
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    private function executeCallable($target, RequestInterface $request, ResponseInterface $response)
    {
        ob_start();
        $level = ob_get_level();

        try {
            $arguments = array_merge([$request, $response], $this->arguments);
            $target = self::getCallable($target, $arguments);
            $return = call_user_func_array($target, $arguments);

            if ($return instanceof ResponseInterface) {
                $response = $return;
                $return = '';
            }

            $return = Utils\Helpers::getOutput($level).$return;
            $body = $response->getBody();

            if ($return !== '' && $body->isWritable()) {
                $body->write($return);
            }

            return $response;
        } catch (\Exception $exception) {
            Utils\Helpers::getOutput($level);
            throw $exception;
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
    private static function getCallable($target, array $construct_args)
    {
        if (empty($target)) {
            throw new RuntimeException('No callable provided');
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

            $fn = new \ReflectionMethod($class, $method);

            if (!$fn->isStatic()) {
                $class = new \ReflectionClass($class);
                $instance = $class->hasMethod('__construct') ? $class->newInstanceArgs($construct_args) : $class->newInstance();
                $target = [$instance, $method];
            }
        }

        //if it's callable as is
        if (is_callable($target)) {
            return $target;
        }

        throw new RuntimeException('Invalid callable provided');
    }
}
