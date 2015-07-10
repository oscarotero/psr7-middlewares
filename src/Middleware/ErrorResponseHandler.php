<?php
namespace Psr7Middlewares\Middleware;

use RuntimeException;
use Aura\Router\RouterContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ErrorResponseHandler
{
    use RouterTrait;

    protected $handler;
    protected $extraArguments;

    /**
     * Creates an instance of this middleware
     *
     * @param callable    $handler
     * @param null|array  $extraArguments
     *
     * @return ErrorResponseHandler
     */
    public static function create($handler, array $extraArguments = array())
    {
        return new static($handler, $extraArguments);
    }

    /**
     * Constructor
     *
     * @param callable $handler
     * @param array    $extraArguments
     */
    public function __construct($handler, $extraArguments)
    {
        $this->handler = $handler;
        $this->extraArguments = $extraArguments;
    }

    /**
     * Execute the middleware
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            $response = $next($request, $response);
        } catch (\Exception $exception) {
            $request = $request->withAttribute('EXCEPTION', $exception);
            $response = $response->withStatus(500);
        }

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            return self::executeTarget($this->handler, $this->extraArguments, $request, $response);
        }

        return $response;
    }
}
