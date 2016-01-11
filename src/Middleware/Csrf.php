<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Exception;

/**
 * Middleware for CSRF protection
 * Code inspired from https://github.com/paragonie/anti-csrf
 */
class Csrf
{
    use Utils\FormTrait;

    protected $maxTokens = 100;
    protected $sessionIndex = 'CSRF';
    protected $formIndex = '_CSRF_INDEX';
    protected $formToken = '_CSRF_TOKEN';

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
            throw new RuntimeException('Csrf middleware needs FormatNegotiator executed before');
        }

        if (!Middleware::hasAttribute($request, ClientIp::KEY)) {
            throw new RuntimeException('Csrf middleware needs ClientIp executed before');
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Csrf middleware needs an active php session');
        }

        if (FormatNegotiator::getFormat($request) !== 'html') {
            return $next($request, $response);
        }

        if (Utils\Helpers::isPost($request) && !$this->validateRequest($request)) {
            return $response->withStatus(403);
        }

        $response = $next($request, $response);

        return $this->insertIntoPostForms($response, function ($match) use ($request) {
            preg_match('/action=["\']?([^"\'\s]+)["\']?/i', $match[0], $matches);

            $action = empty($matches[1]) ? $request->getUri()->getPath() : $matches[1];
            list($index, $token) = $this->getTokenArray($request, $action);

            return $match[0]
                .'<input type="text" name="'.$this->formIndex.'" value="'.htmlentities($index, ENT_QUOTES, 'UTF-8').'">'
                .'<input type="text" name="'.$this->formToken.'" value="'.htmlentities($token, ENT_QUOTES, 'UTF-8').'">';
        });

        return $response;
    }

    /**
     * Retrieve a token array
     *
     * @return array
     */
    public function getTokenArray($request, $lockTo)
    {
        if (!isset($_SESSION[$this->sessionIndex])) {
            $_SESSION[$this->sessionIndex] = [];
        }

        $index = self::encode(random_bytes(18));
        $token = self::encode(random_bytes(32));

        $_SESSION[$this->sessionIndex][$index] = [
            'created' => intval(date('YmdHis')),
            'uri' => $request->getUri()->getPath(),
            'token' => $token,
            'lockTo' => $lockTo
        ];

        $this->recycleTokens();

        $token = self::encode(hash_hmac('sha256', ClientIp::getIp($request), base64_decode($token), true));

        return [$index, $token];
    }

    /**
     * Validate a request based on $this->session and $this->post data
     *
     * @return boolean
     */
    public function validateRequest($request)
    {
        if (!isset($_SESSION[$this->sessionIndex])) {
            $_SESSION[$this->sessionIndex] = [];
            return false;
        }


        $data = $request->getParsedBody();

        if (!isset($data[$this->formIndex]) || !isset($data[$this->formToken])) {
            return false;
        }

        $index = $data[$this->formIndex];
        $token = $data[$this->formToken];

        if (!isset($_SESSION[$this->sessionIndex][$index])) {
            return false;
        }

        $stored = $_SESSION[$this->sessionIndex][$index];
        unset($_SESSION[$this->sessionIndex][$index]);

        $lockTo = $request->getUri()->getPath();

        if (!hash_equals($lockTo, $stored['lockTo'])) {
            return false;
        }

        $expected = self::encode(hash_hmac('sha256', ClientIp::getIp($request), base64_decode($stored['token']), true));

        return hash_equals($token, $expected);
    }

    /**
     * Enforce an upper limit on the number of tokens stored in session state
     * by removing the oldest tokens first.
     */
    protected function recycleTokens()
    {
        if (!$this->maxTokens || count($_SESSION[$this->sessionIndex]) <= $this->maxTokens) {
            return;
        }

        uasort($_SESSION[$this->sessionIndex], function($a, $b) {
            return $a['created'] - $b['created'];
        });

        while (count($_SESSION[$this->sessionIndex]) > $this->maxTokens) {
            array_shift($_SESSION[$this->sessionIndex]);
        }
    }

    /**
     * Encode string with base64, but strip padding.
     * PHP base64_decode does not croak on that.
     *
     * @param string $s
     * @return string
     */
    protected static function encode($s)
    {
        return rtrim(base64_encode($s), '=');
    }
}
