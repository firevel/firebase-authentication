<?php

namespace Firevel\FirebaseAuthentication\Contracts;

use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;

/**
 * Verifies a Firebase ID token and returns the decoded token contract.
 *
 * The default implementation wraps `Kreait\Firebase\JWT\IdTokenVerifier`.
 * Tests can swap in a fake via the container to bypass real verification;
 * applications can plug in tenant-aware or otherwise customised verifiers.
 */
interface TokenVerifier
{
    /**
     * Verify a Firebase ID token using strict timing checks.
     *
     * @throws IdTokenVerificationFailed
     */
    public function verifyIdToken(string $token): Token;

    /**
     * Verify a Firebase ID token, tolerating a few seconds of clock skew
     * on the iat/exp/auth_time claims.
     *
     * @throws IdTokenVerificationFailed
     */
    public function verifyIdTokenWithLeeway(string $token, int $leewayInSeconds): Token;
}
