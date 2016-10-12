<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to span protection using the honeypot technique.
 */
class Honeypot
{
    use Utils\FormTrait;
    use Utils\AttributeTrait;

    const KEY_GENERATOR = 'HONEYPOT_GENERATOR';

    /**
     * @var string The honeypot input name
     */
    private $inputName = 'hpt_name';

    /**
     * @var string The honeypot class name
     */
    private $inputClass = 'hpt_input';

    /**
     * Returns a callable to generate the inputs.
     *
     * @param ServerRequestInterface $request
     *
     * @return callable|null
     */
    public static function getGenerator(ServerRequestInterface $request)
    {
        return self::getAttribute($request, self::KEY_GENERATOR);
    }

    /**
     * Set the field name.
     *
     * @param string $inputName
     *
     * @return self
     */
    public function inputName($inputName)
    {
        $this->inputName = $inputName;

        return $this;
    }

    /**
     * Set the field class.
     *
     * @param string $inputClass
     *
     * @return self
     */
    public function inputClass($inputClass)
    {
        $this->inputClass = $inputClass;

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
        if (Utils\Helpers::getMimeType($response) !== 'text/html') {
            return $next($request, $response);
        }

        if (Utils\Helpers::isPost($request) && !$this->isValid($request)) {
            return $response->withStatus(403);
        }

        $generator = function () {
            return '<input type="text" name="'.$this->inputName.'" class="'.$this->inputClass.'">';
        };

        if (!$this->autoInsert) {
            $request = self::setAttribute($request, self::KEY_GENERATOR, $generator);

            return $next($request, $response);
        }

        $response = $next($request, $response);

        return $this->insertIntoPostForms($response, function ($match) use ($generator) {
            return $match[0].$generator();
        });
    }

    /**
     * Check whether the request is valid.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function isValid(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();

        return isset($data[$this->inputName]) && $data[$this->inputName] === '';
    }
}
