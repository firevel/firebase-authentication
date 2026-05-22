<?php

namespace Firevel\FirebaseAuthentication;

use Firevel\FirebaseAuthentication\Contracts\TokenVerifier;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\IdTokenVerifier;

class KreaitTokenVerifier implements TokenVerifier
{
    public function __construct(private readonly IdTokenVerifier $verifier) {}

    public function verifyIdToken(string $token): Token
    {
        return $this->verifier->verifyIdToken($token);
    }

    public function verifyIdTokenWithLeeway(string $token, int $leewayInSeconds): Token
    {
        return $this->verifier->verifyIdTokenWithLeeway($token, $leewayInSeconds);
    }
}
