<?php

namespace Firevel\FirebaseAuthentication\Filters;

use Firevel\FirebaseAuthentication\Contracts\ClaimFilter;

class CallbackClaimFilter implements ClaimFilter
{
    /**
     * @var callable
     */
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Delegate to the wrapped callable. The callable receives the value first
     * for ergonomics: fn ($value, $claim, $claims) => ...
     */
    public function filter(string $claim, mixed $value, array $claims): mixed
    {
        return ($this->callback)($value, $claim, $claims);
    }
}
