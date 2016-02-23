<?php

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\Middleware;
use Psr7Middlewares\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use InvalidArgumentException;
use RuntimeException;
use ArrayAccess;

/**
 * Middleware for CSRF protection
 * Code inspired from https://github.com/paragonie/anti-csrf.
 */
class Csrf
{
    use Utils\FormTrait;

    const KEY = 'CSRF';

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

        if (!Middleware::hasAttribute($request, Middleware::STORAGE_KEY)) {
            throw new RuntimeException('Csrf middleware needs a storage defined');
        }

        if (FormatNegotiator::getFormat($request) !== 'html') {
            return $next($request, $response);
        }

        $storage = Middleware::getAttribute($request, Middleware::STORAGE_KEY);
        $tokens = $storage->get(self::KEY) ?: [];

        if (Utils\Helpers::isPost($request) && !$this->validateRequest($request, $tokens)) {
            return $response->withStatus(403);
        }

        $response = $next($request, $response);

        $response = $this->insertIntoPostForms($response, function ($match) use ($request, &$tokens) {
            preg_match('/action=["\']?([^"\'\s]+)["\']?/i', $match[0], $matches);

            $action = empty($matches[1]) ? $request->getUri()->getPath() : $matches[1];

            return $match[0].$this->generateTokens($request, $action, $tokens);
        });

        $storage->set(self::KEY, $tokens);

        return $response;
    }

    /**
     * Generate and retrieve the tokens.
     * 
     * @param ServerRequestInterface $request
     * @param string                 $lockTo
     * @param array                 $tokens
     *
     * @return string
     */
    private function generateTokens(ServerRequestInterface $request, $lockTo, array &$tokens)
    {
        $index = self::encode(random_bytes(18));
        $token = self::encode(random_bytes(32));

        $tokens[$index] = [
            'created' => intval(date('YmdHis')),
            'uri' => $request->getUri()->getPath(),
            'token' => $token,
            'lockTo' => $lockTo,
        ];

        $this->recycleTokens($tokens);

        $token = self::encode(hash_hmac('sha256', ClientIp::getIp($request), base64_decode($token), true));

        return '<input type="hidden" name="'.$this->formIndex.'" value="'.htmlentities($index, ENT_QUOTES, 'UTF-8').'">'
               .'<input type="hidden" name="'.$this->formToken.'" value="'.htmlentities($token, ENT_QUOTES, 'UTF-8').'">';
    }

    /**
     * Validate the request.
     * 
     * @param ServerRequestInterface $request
     * @param array &$tokens
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
     * Enforce an upper limit on the number of tokens stored in session state
     * by removing the oldest tokens first.
     * 
     * @param array &$tokens
     */
    private function recycleTokens(array &$tokens)
    {
        if (!$this->maxTokens || count($tokens) <= $this->maxTokens) {
            return;
        }

        uasort($tokens, function ($a, $b) {
            return $a['created'] - $b['created'];
        });

        while (count($tokens) > $this->maxTokens) {
            array_shift($tokens);
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
