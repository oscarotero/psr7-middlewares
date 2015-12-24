<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\JsonResponseHandler;

/**
 * Middleware to use whoops as error handler.
 */
class Whoops
{
    /**
     * @var Run|null To handle errors using whoops
     */
    private $whoops;

    /**
     * @var bool Whether catch errors or not
     */
    private $catchErrors = true;

    /**
     * Constructor.Set the whoops instance.
     *
     * @param Run $whoops
     */
    public function __construct(Run $whoops = null)
    {
        if ($whoops !== null) {
            $this->whoops($whoops);
        }
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
     * Whether catch errors or not.
     *
     * @param bool $catchErrors
     *
     * @return self
     */
    public function catchErrors($catchErrors = true)
    {
        $this->catchErrors = $catchErrors;

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
        $whoops = $this->getWhoopsInstance($request);

        //Catch errors means register whoops globally
        if ($this->catchErrors) {
            $whoops->register();
        }

        try {
            $response = $next($request, $response);
        } catch (\Exception $exception) {
            $method = Run::EXCEPTION_HANDLER;

            $whoops->allowQuit(false);
            $whoops->writeToOutput(false);

            $body = Middleware::createStream();
            $body->write($whoops->$method($exception));

            $response = $response->withStatus(500)->withBody($body);
        }

        $whoops->unregister();

        return $response;
    }

    /**
     * Returns the whoops instance or create one.
     * 
     * @param ServerRequestInterface $request
     *
     * @return Run
     */
    private function getWhoopsInstance(ServerRequestInterface $request)
    {
        if ($this->whoops) {
            return $this->whoops;
        }

        $whoops = new Run();

        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->pushHandler(new PlainTextHandler());

        $jsonHandler = new JsonResponseHandler();
        $jsonHandler->onlyForAjaxRequests(true);
        $whoops->pushHandler($jsonHandler);

        return $this->whoops = $whoops;
    }
}
