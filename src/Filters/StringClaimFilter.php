<?php

namespace Firevel\FirebaseAuthentication\Filters;

use Firevel\FirebaseAuthentication\Contracts\ClaimFilter;

class StringClaimFilter implements ClaimFilter
{
    /**
     * Accept scalar claims, stored as a string. Arrays/objects are rejected.
     */
    public function filter(string $claim, mixed $value, array $claims): mixed
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = (string) $value;

        return $value === '' ? null : $value;
    }
}
