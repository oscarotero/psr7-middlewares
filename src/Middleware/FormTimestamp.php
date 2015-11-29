<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Exception;

/**
 * Middleware to span protection using the timestamp value in forms.
 */
class FormTimestamp
{
    use Utils\FormTrait;
    use Utils\CryptTrait;

    /**
     * @var string The honeypot input name
     */
    protected $inputName = 'hpt_time';

    /**
     * @var int Minimum seconds to determine whether the request is a bot
     */
    protected $min = 3;

    /**
     * @var int Max seconds to expire the form. Zero to do not expire
     */
    protected $max = 0;

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
     * Minimum time required.
     * 
     * @param int $seconds
     * 
     * @return self
     */
    public function min($seconds)
    {
        $this->min = $seconds;

        return $this;
    }

    /**
     * Max time before expire the form.
     * 
     * @param int $seconds
     * 
     * @return self
     */
    public function max($seconds)
    {
        $this->max = $seconds;

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
            throw new RuntimeException('FormTimestamp middleware needs FormatNegotiator executed before');
        }

        if (FormatNegotiator::getFormat($request) !== 'html') {
            return $next($request, $response);
        }

        if ($this->isPost($request) && !$this->isValid($request)) {
            return $response->withStatus(403);
        }

        $response = $next($request, $response);

        return $this->insertIntoPostForms($response, '<input type="hidden" name="'.$this->inputName.'" value="'.$this->encrypt(time()).'">');
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

        //value does not exists
        if (empty($data[$this->inputName])) {
            return false;
        }

        try {
            $time = $this->decrypt($data[$this->inputName]);
        } catch (Exception $e) {
            return false;
        }

        //value is not valid
        if (!is_numeric($time)) {
            return false;
        }

        $now = time();

        //sent from future
        if ($now < $time) {
            return false;
        }

        $diff = $now - $time;

        //check min
        if ($diff < $this->min) {
            return false;
        }

        //check max
        if ($this->max && $diff > $this->max) {
            return false;
        }

        return true;
    }
}
