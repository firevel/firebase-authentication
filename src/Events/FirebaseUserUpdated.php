<?php

namespace Firevel\FirebaseAuthentication\Events;

/**
 * Fired when Firebase sign-in updates an existing user row (claims drifted
 * from stored attributes). Not fired when the row is unchanged.
 */
class FirebaseUserUpdated
{
    public function __construct(
        public readonly object $user,
        public readonly array $claims,
    ) {
    }
}
