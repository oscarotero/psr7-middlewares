<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Whoops\Run;

/**
 * Middleware to handle php errors and exceptions.
 */
class ErrorHandler
{
    const KEY = 'EXCEPTION';

    use Utils\HandlerTrait;

    /**
     * @var Run|null To handle errors using whoops
     */
    protected $whoops;

    /**
     * @var bool Whether or not catch exceptions
     */
    protected $catchExceptions = false;

    /**
     * Returns the exception throwed.
     *
     * @param ServerRequestInterface $request
     *
     * @return \Exception|null
     */
    public static function getException(ServerRequestInterface $request)
    {
        return Middleware::getAttribute($request, self::KEY);
    }

    /**
     * Set an instance of Whoops.
     *
     * @param Run $whoops
     *
     * @return self
     */
    public function whoops(Run $whoops)
    {
        $this->whoops = $whoops;

        return $this;
    }

    /**
     * Configure the catchExceptions.
     *
     * @param bool $catch
     *
     * @return self
     */
    public function catchExceptions($catch = true)
    {
        $this->catchExceptions = (boolean) $catch;

        return $this;
    }

    /**
     * Execute the middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($this->whoops) {
            $this->whoops->pushHandler(function ($exception) use ($request, $response) {
                try {
                    echo $this->executeHandler(Middleware::setAttribute($request, self::KEY, $exception), $response)->getBody();
                } catch (\Exception $exception) {
                    //ignored
                }
            });
        }

        ob_start();

        try {
            $response = $next($request, $response);
        } catch (\Exception $exception) {
            if (!$this->catchExceptions) {
                throw $exception;
            }

            $request = Middleware::setAttribute($request, self::KEY, $exception);
            $response = $response->withStatus(500);
        }

        ob_end_clean();

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            try {
                return $this->executeHandler($request, $response);
            } catch (\Exception $exception) {
                //ignored
            }
        }

        if ($this->whoops) {
            $this->whoops->popHandler();
        }

        return $response;
    }
}
