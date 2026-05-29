<?php

namespace Firevel\FirebaseAuthentication\Contracts;

interface ClaimFilter
{
    /**
     * Validate and/or transform a raw JWT claim value before it is written
     * to the user model.
     *
     * Implementations act as a security layer: enforce the expected type,
     * coerce when safe, and return null to reject (skip) a value that does
     * not validate — rejected claims keep the mapped attribute's existing
     * value on update and leave it unset on create.
     *
     * @param  string  $claim  The token claim key being filtered.
     * @param  mixed  $value  The raw claim value.
     * @param  array  $claims  All decoded JWT claims, for context.
     * @return mixed The filtered value, or null to skip the claim.
     */
    public function filter(string $claim, mixed $value, array $claims): mixed;
}
