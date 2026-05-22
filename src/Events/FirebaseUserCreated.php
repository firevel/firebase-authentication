<?php

namespace Firevel\FirebaseAuthentication\Events;

/**
 * Fired when Firebase sign-in produces a brand new user row in the database.
 * Use this to send welcome emails, provision tenant resources, etc.
 */
class FirebaseUserCreated
{
    public function __construct(
        public readonly object $user,
        public readonly array $claims,
    ) {}
}
