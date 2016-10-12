<?php

namespace Psr7Middlewares\Utils;

use Psr\Http\Message\ResponseInterface;

/**
 * Utilities used by middlewares that manipulate forms.
 */
trait FormTrait
{
    use StreamTrait;

    private $autoInsert = false;

    /**
     * Configure if autoinsert or not the inputs automatically.
     *
     * @param bool $autoInsert
     *
     * @return self
     */
    public function autoInsert($autoInsert = true)
    {
        $this->autoInsert = $autoInsert;

        return $this;
    }

    /**
     * Insert content into all POST forms.
     *
     * @param ResponseInterface $response
     * @param callable          $replace
     *
     * @return ResponseInterface
     */
    private function insertIntoPostForms(ResponseInterface $response, callable $replace)
    {
        $html = (string) $response->getBody();
        $html = preg_replace_callback('/(<form\s[^>]*method=["\']?POST["\']?[^>]*>)/i', $replace, $html, -1, $count);

        if (!empty($count)) {
            $body = self::createStream();
            $body->write($html);

            return $response->withBody($body);
        }

        return $response;
    }
}
