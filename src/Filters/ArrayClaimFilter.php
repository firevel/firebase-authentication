<?php

namespace Firevel\FirebaseAuthentication\Filters;

use Firevel\FirebaseAuthentication\Contracts\ClaimFilter;

class ArrayClaimFilter implements ClaimFilter
{
    /**
     * Accept non-empty array claims as-is. Scalars and empty arrays are
     * rejected. Pair with an Eloquent `array`/`json` cast on the model column.
     */
    public function filter(string $claim, mixed $value, array $claims): mixed
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        return $value;
    }
}
