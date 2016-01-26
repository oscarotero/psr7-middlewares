<?php declare(strict_types=1);

namespace Psr7Middlewares\Middleware;

use Psr7Middlewares\{Middleware, Utils};
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
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
     * Set the field name.
     * 
     * @param string $inputName
     * 
     * @return self
     */
    public function inputName(string $inputName): self
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
    public function min(int $seconds): self
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
    public function max(int $seconds): self
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if (!Middleware::hasAttribute($request, FormatNegotiator::KEY)) {
            throw new RuntimeException('FormTimestamp middleware needs FormatNegotiator executed before');
        }

        if (FormatNegotiator::getFormat($request) !== 'html') {
            return $next($request, $response);
        }

        if (Utils\Helpers::isPost($request) && !$this->isValid($request)) {
            return $response->withStatus(403);
        }

        $response = $next($request, $response);

        $value = $this->encrypt((string) time());

        return $this->insertIntoPostForms($response, function ($match) use ($value) {
            return $match[0].'<input type="hidden" name="'.$this->inputName.'" value="'.$value.'">';
        });
    }

    /**
     * Check whether the request is valid.
     * 
     * @param ServerRequestInterface $request
     * 
     * @return bool
     */
    private function isValid(ServerRequestInterface $request): bool
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
