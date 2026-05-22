<?php

namespace Firevel\FirebaseAuthentication\Testing;

use Firevel\FirebaseAuthentication\Contracts\TokenVerifier;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;

/**
 * In-memory verifier for tests. Returns a configured payload regardless of
 * the bearer token presented, or throws to simulate verification failure.
 *
 * Drive it via the `FirebaseAuth` helper rather than instantiating directly.
 */
class FakeTokenVerifier implements TokenVerifier
{
    private ?array $claims = null;

    private bool $shouldFail = false;

    private string $failureMessage = 'Invalid Firebase ID token (fake).';

    public function setClaims(array $claims): self
    {
        $this->claims = $claims;
        $this->shouldFail = false;

        return $this;
    }

    public function failWith(string $message = 'Invalid Firebase ID token (fake).'): self
    {
        $this->shouldFail = true;
        $this->failureMessage = $message;

        return $this;
    }

    public function reset(): self
    {
        $this->claims = null;
        $this->shouldFail = false;

        return $this;
    }

    public function verifyIdToken(string $token): Token
    {
        if ($this->shouldFail) {
            throw new IdTokenVerificationFailed($this->failureMessage);
        }

        if ($this->claims === null) {
            throw new IdTokenVerificationFailed(
                'FakeTokenVerifier has no claims configured. Call FirebaseAuth::actingAs($claims) first.'
            );
        }

        return new FakeToken($this->claims, $token);
    }

    public function verifyIdTokenWithLeeway(string $token, int $leewayInSeconds): Token
    {
        return $this->verifyIdToken($token);
    }
}
