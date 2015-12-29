<?php

namespace Psr7Middlewares\Utils;

use Negotiation\AbstractNegotiator;

/**
 * Utilities used by middlewares that use a negotiator.
 */
trait NegotiateTrait
{
    /**
     * Returns the best value of a header.
     *
     * @param string             $accept     The header to negotiate
     * @param AbstractNegotiator $negotiator
     * @param array              $priorities
     *
     * @return string|null
     */
    private function negotiateHeader($accept, AbstractNegotiator $negotiator, array $priorities)
    {
        if (empty($accept) || empty($priorities)) {
            return;
        }

        try {
            $best = $negotiator->getBest($accept, $priorities);
        } catch (\Exception $exception) {
            return;
        }

        if ($best) {
            return $best->getValue();
        }
    }
}
