<?php

namespace Firevel\FirebaseAuthentication\Filters;

use Firevel\FirebaseAuthentication\Contracts\ClaimFilter;

class IntegerClaimFilter implements ClaimFilter
{
    /**
     * Accept integer-valued claims (int or integer-like string), cast to int.
     * Floats, non-numeric strings, booleans and arrays are rejected.
     */
    public function filter(string $claim, mixed $value, array $claims): mixed
    {
        if (is_bool($value) || ! is_scalar($value)) {
            return null;
        }

        $int = filter_var($value, FILTER_VALIDATE_INT);

        return $int === false ? null : $int;
    }
}
