<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware to span protection using the honeypot technique.
 */
class Honeypot
{
    use Utils\FormTrait;

    /**
     * @var string The honeypot input name
     */
    protected $inputName = 'hpt_name';

    /**
     * @var string The honeypot class name
     */
    protected $inputClass = 'hpt_input';

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
        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('Honeypot middleware needs FormatNegotiator executed before');
        }

        if (FormatNegotiator::getFormat($request) !== 'html') {
            return $next($request, $response);
        }

        if ($this->isPost($request) && !$this->isValid($request)) {
            return $response->withStatus(403);
        }

        $response = $next($request, $response);

        return $this->insertIntoPostForms($response, '<input type="text" name="'.$this->inputName.'" class="'.$this->inputClass.'">');
    }

    /**
     * Check whether the request is valid.
     * 
     * @param ServerRequestInterface $request
     * 
     * @return bool
     */
    protected function isValid(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();

        return isset($data[$this->inputName]) && $data[$this->inputName] === '';
    }
}
