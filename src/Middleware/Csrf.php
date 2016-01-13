<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use ArrayAccess;

/**
 * Middleware for CSRF protection
 * Code inspired from https://github.com/paragonie/anti-csrf.
 */
class Csrf
{
    use Utils\FormTrait;

    /**
     * @var int Max number of CSRF tokens
     */
    private $maxTokens = 100;

    /**
     * @var string field name with the CSRF index
     */
    private $formIndex = '_CSRF_INDEX';

    /**
     * @var string field name with the CSRF token
     */
    private $formToken = '_CSRF_TOKEN';

    /*
     * @var array|ArrayAccess CSRF storage
     */
    private $storage;

    /**
     * @var string Index used in the storage
     */
    private $sessionIndex = 'CSRF';

    /**
     * Set the storage of the CSRF.
     * 
     * @param array|ArrayAccess|null $storage
     */
    public function __construct(&$storage = null)
    {
        if (is_array($storage)) {
            $this->storage = &$storage;
        } elseif ($storage instanceof ArrayAccess) {
            $this->storage = $storage;
        } elseif ($storage !== null) {
            throw new InvalidArgumentException('The storage argument must be an array, ArrayAccess or null');
        }
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
            throw new RuntimeException('Csrf middleware needs FormatNegotiator executed before');
        }

        if (!Middleware::hasAttribute($request, ClientIp::KEY)) {
            throw new RuntimeException('Csrf middleware needs ClientIp executed before');
        }

        if ($this->storage === null) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                throw new RuntimeException('Csrf middleware needs an active php session or a storage defined');
            }

            if (!isset($_SESSION[$this->sessionIndex])) {
                $_SESSION[$this->sessionIndex] = [];
            }

            $this->storage = &$_SESSION[$this->sessionIndex];
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

            return $match[0].$this->generateTokens($request, $action);
        });
    }

    /**
     * Generate and retrieve the tokens.
     * 
     * @param ServerRequestInterface $request
     * @param string                 $lockTo
     *
     * @return array
     */
    private function generateTokens(ServerRequestInterface $request, $lockTo)
    {
        $index = self::encode(random_bytes(18));
        $token = self::encode(random_bytes(32));

        $this->storage[$index] = [
            'created' => intval(date('YmdHis')),
            'uri' => $request->getUri()->getPath(),
            'token' => $token,
            'lockTo' => $lockTo,
        ];

        $this->recycleTokens();

        $token = self::encode(hash_hmac('sha256', ClientIp::getIp($request), base64_decode($token), true));

        return '<input type="hidden" name="'.$this->formIndex.'" value="'.htmlentities($index, ENT_QUOTES, 'UTF-8').'">'
               .'<input type="hidden" name="'.$this->formToken.'" value="'.htmlentities($token, ENT_QUOTES, 'UTF-8').'">';
    }

    /**
     * Validate the request.
     * 
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function validateRequest(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();

        if (!isset($data[$this->formIndex]) || !isset($data[$this->formToken])) {
            return false;
        }

        $index = $data[$this->formIndex];
        $token = $data[$this->formToken];

        if (!isset($this->storage[$index])) {
            return false;
        }

        $stored = $this->storage[$index];
        unset($this->storage[$index]);

        $lockTo = $request->getUri()->getPath();

        if (!Utils\Helpers::hashEquals($lockTo, $stored['lockTo'])) {
            return false;
        }

        $expected = self::encode(hash_hmac('sha256', ClientIp::getIp($request), base64_decode($stored['token']), true));

        return Utils\Helpers::hashEquals($token, $expected);
    }

    /**
     * Enforce an upper limit on the number of tokens stored in session state
     * by removing the oldest tokens first.
     */
    private function recycleTokens()
    {
        if (!$this->maxTokens || count($this->storage) <= $this->maxTokens) {
            return;
        }

        uasort($this->storage, function ($a, $b) {
            return $a['created'] - $b['created'];
        });

        while (count($this->storage) > $this->maxTokens) {
            array_shift($this->storage);
        }
    }

    /**
     * Encode string with base64, but strip padding.
     * PHP base64_decode does not croak on that.
     *
     * @param string $value
     *
     * @return string
     */
    private static function encode($value)
    {
        return rtrim(base64_encode($value), '=');
    }
}
