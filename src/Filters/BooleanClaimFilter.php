<?php

namespace Firevel\FirebaseAuthentication\Filters;

use Firevel\FirebaseAuthentication\Contracts\ClaimFilter;

class BooleanClaimFilter implements ClaimFilter
{
    /**
     * Accept boolean-valued claims (true/false, 1/0, "true"/"false",
     * "1"/"0", "yes"/"no", "on"/"off"). Anything ambiguous is rejected.
     */
    public function filter(string $claim, mixed $value, array $claims): mixed
    {
        if (! is_scalar($value)) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
