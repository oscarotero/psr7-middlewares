<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Middleware for CSRF protection
 * Code inspired from https://github.com/paragonie/anti-csrf.
 */
class Csrf
{
    use Utils\FormTrait;
    use Utils\StorageTrait;

    const KEY = 'CSRF';
    const KEY_GENERATOR = 'CSRF_GENERATOR';

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
        if (!self::hasAttribute($request, ClientIp::KEY)) {
            throw new RuntimeException('Csrf middleware needs ClientIp executed before');
        }

        if (Utils\Helpers::getMimeType($response) !== 'text/html') {
            return $next($request, $response);
        }

        $tokens = &self::getStorage($request, self::KEY);

        if (Utils\Helpers::isPost($request) && !$this->validateRequest($request, $tokens)) {
            return $response->withStatus(403);
        }

        $generator = function ($action = null) use ($request, &$tokens) {
            if (empty($action)) {
                $action = $request->getUri()->getPath();
            }

            return $this->generateTokens($request, $action, $tokens);
        };

        if (!$this->autoInsert) {
            $request = self::setAttribute($request, self::KEY_GENERATOR, $generator);

            return $next($request, $response);
        }

        $response = $next($request, $response);

        return $this->insertIntoPostForms($response, function ($match) use ($generator) {
            preg_match('/action=["\']?([^"\'\s]+)["\']?/i', $match[0], $matches);

            return $match[0].$generator(isset($matches[1]) ? $matches[1] : null);
        });
    }

    /**
     * Generate and retrieve the tokens.
     *
     * @param ServerRequestInterface $request
     * @param string                 $lockTo
     * @param array                  $tokens
     *
     * @return string
     */
    private function generateTokens(ServerRequestInterface $request, $lockTo, array &$tokens)
    {
        $index = self::encode($this->randomToken(18));
        $token = self::encode($this->randomToken(32));

        $tokens[$index] = [
            'uri' => $request->getUri()->getPath(),
            'token' => $token,
            'lockTo' => $lockTo,
        ];

        if ($this->maxTokens > 0 && ($total = count($tokens)) > $this->maxTokens) {
            array_splice($tokens, 0, $total - $this->maxTokens);
        }

        $token = self::encode(hash_hmac('sha256', ClientIp::getIp($request), base64_decode($token), true));

        return '<input type="hidden" name="'.$this->formIndex.'" value="'.htmlentities($index, ENT_QUOTES, 'UTF-8').'">'
               .'<input type="hidden" name="'.$this->formToken.'" value="'.htmlentities($token, ENT_QUOTES, 'UTF-8').'">';
    }

    /**
     * Validate the request.
     *
     * @param ServerRequestInterface $request
     * @param array                  &$tokens
     *
     * @return bool
     */
    private function validateRequest(ServerRequestInterface $request, array &$tokens)
    {
        $data = $request->getParsedBody();

        if (!isset($data[$this->formIndex]) || !isset($data[$this->formToken])) {
            return false;
        }

        $index = $data[$this->formIndex];
        $token = $data[$this->formToken];

        if (!isset($tokens[$index])) {
            return false;
        }

        $stored = $tokens[$index];
        unset($tokens[$index]);

        $lockTo = $request->getUri()->getPath();

        if (!Utils\Helpers::hashEquals($lockTo, $stored['lockTo'])) {
            return false;
        }

        $expected = self::encode(hash_hmac('sha256', ClientIp::getIp($request), base64_decode($stored['token']), true));

        return Utils\Helpers::hashEquals($token, $expected);
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

    /**
     * Return a random token.
     *
     * @param int $length The length of the random string that should be returned in bytes
     *
     * @return string
     */
    private function randomToken($length = 32)
    {
        if (!isset($length) || intval($length) <= 8) {
            $length = 32;
        }
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }
        if (function_exists('mcrypt_create_iv')) {
            return mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return openssl_random_pseudo_bytes($length);
        }

        return @crypt(uniqid());
    }
}
