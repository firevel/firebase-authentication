<?php

namespace Firevel\FirebaseAuthentication;

/**
 * Backwards-compatibility alias for the v2 trait name.
 *
 * The canonical spelling is `FirebaseAuthenticatable` (matches Laravel's
 * `Illuminate\Contracts\Auth\Authenticatable`). The original name was
 * misspelled and is kept here so existing `use FirebaseAuthenticable;`
 * declarations on User models continue to work without changes.
 *
 * @deprecated since 3.0.0, use {@see FirebaseAuthenticatable} instead.
 */
trait FirebaseAuthenticable
{
    use FirebaseAuthenticatable;
}
