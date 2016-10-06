<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\XmlResponseHandler;

/**
 * Middleware to use whoops as error handler.
 */
class Whoops
{
    use Utils\StreamTrait;

    /**
     * @var Run|null The provided instance of Whoops
     */
    private $whoops;

    /**
     * @var bool Whether catch errors or not
     */
    private $catchErrors = true;

    /**
     * Set the whoops instance.
     *
     * @param Run|null $whoops
     */
    public function __construct(Run $whoops = null)
    {
        $this->whoops = $whoops;
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
        $this->catchErrors = (bool) $catchErrors;

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
        ob_start();
        $level = ob_get_level();

        $method = Run::EXCEPTION_HANDLER;
        $whoops = $this->getWhoopsInstance($request);

        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->sendHttpCode(false);

        //Catch errors means register whoops globally
        if ($this->catchErrors) {
            $whoops->register();
        }

        try {
            $response = $next($request, $response);
        } catch (\Throwable $exception) {
            $body = self::createStream($response->getBody());
            $body->write($whoops->$method($exception));
            $response = $response->withStatus(500)->withBody($body);
        } catch (\Exception $exception) {
            $body = self::createStream($response->getBody());
            $body->write($whoops->$method($exception));
            $response = $response->withStatus(500)->withBody($body);
        } finally {
            Utils\Helpers::getOutput($level);
        }

        if ($this->catchErrors) {
            $whoops->unregister();
        }

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

        if (php_sapi_name() === 'cli') {
            $whoops->pushHandler(new PlainTextHandler());

            return $whoops;
        }

        $format = FormatNegotiator::getFormat($request);

        switch ($format) {
            case 'json':
                $whoops->pushHandler(new JsonResponseHandler());
                break;

            case 'html':
                $whoops->pushHandler(new PrettyPageHandler());
                break;

            case 'xml':
                $whoops->pushHandler(new XmlResponseHandler());
                break;

            case 'txt':
            case 'css':
            case 'js':
                $whoops->pushHandler(new PlainTextHandler());
                break;

            default:
                if (empty($format)) {
                    $whoops->pushHandler(new PrettyPageHandler());
                } else {
                    $whoops->pushHandler(new PlainTextHandler());
                }

                break;
        }

        return $whoops;
    }
}
