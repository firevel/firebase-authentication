<?php

namespace Firevel\FirebaseAuthentication\Events;

/**
 * Fired after a user has been resolved from a verified Firebase ID token,
 * regardless of whether the user was newly created, updated, or unchanged.
 */
class FirebaseUserResolved
{
    public function __construct(
        public readonly object $user,
        public readonly array $claims,
    ) {}
}
