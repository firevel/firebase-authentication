<?php

namespace Firevel\FirebaseAuthentication\Filters;

use Firevel\FirebaseAuthentication\Contracts\ClaimFilter;

class UrlClaimFilter implements ClaimFilter
{
    /**
     * Accept only http/https URL claims. This rejects the oversized inline
     * `data:` blob URIs Firebase occasionally emits for the `picture` claim,
     * as well as other non-URL or unsupported-scheme values.
     */
    public function filter(string $claim, mixed $value, array $claims): mixed
    {
        if (! is_string($value)) {
            return null;
        }

        return preg_match('#^https?://#i', $value) === 1 ? $value : null;
    }
}
