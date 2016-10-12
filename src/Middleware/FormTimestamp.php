<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Exception;

/**
 * Middleware to span protection using the timestamp value in forms.
 */
class FormTimestamp
{
    use Utils\FormTrait;
    use Utils\CryptTrait;
    use Utils\AttributeTrait;

    const KEY_GENERATOR = 'FORM_TIMESTAMP_GENERATOR';

    /**
     * @var string The honeypot input name
     */
    private $inputName = 'hpt_time';

    /**
     * @var int Minimum seconds to determine whether the request is a bot
     */
    private $min = 3;

    /**
     * @var int Max seconds to expire the form. Zero to do not expire
     */
    private $max = 0;

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
        if (Utils\Helpers::getMimeType($response) !== 'text/html') {
            return $next($request, $response);
        }

        if (Utils\Helpers::isPost($request) && !$this->isValid($request)) {
            return $response->withStatus(403);
        }

        $value = $this->encrypt(time());

        $generator = function () use ($value) {
            return '<input type="hidden" name="'.$this->inputName.'" value="'.$value.'">';
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
