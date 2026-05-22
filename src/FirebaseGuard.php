<?php

namespace Firevel\FirebaseAuthentication;

use Firevel\FirebaseAuthentication\Contracts\TokenVerifier;
use Illuminate\Http\Request;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;

class FirebaseGuard
{
    protected TokenVerifier $verifier;

    public function __construct(TokenVerifier $verifier)
    {
        $this->verifier = $verifier;
    }

    /**
     * Get User by request claims.
     *
     * @param  string|null  $modelClass  The model class to resolve the user from.
     * @return mixed|null
     */
    public function user(Request $request, ?string $modelClass = null)
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return;
        }

        try {
            $firebaseToken = $this->verifyToken($token);
        } catch (IdTokenVerificationFailed $e) {
            // Expired tokens are routine traffic — never noisy, regardless
            // of debug mode, to avoid log spam from normal client behaviour.
            if (str_contains($e->getMessage(), 'token is expired')) {
                return;
            }

            // Other verification failures (bad signature, wrong audience,
            // malformed JWT) surface in debug mode to aid local development
            // and stay silent in production to avoid noise from probing.
            if (config('app.debug')) {
                throw $e;
            }

            return;
        }

        $model = $modelClass ?? config('auth.providers.users.model');

        $user = app($model)->resolveByClaims($firebaseToken->payload());

        if ($user === null) {
            return;
        }

        return $user->setFirebaseAuthenticationToken($token);
    }

    /**
     * Verify a Firebase ID token, honoring the configured clock-skew leeway.
     */
    protected function verifyToken(string $token)
    {
        $leeway = (int) config('firebase-authentication.leeway', 0);

        if ($leeway > 0) {
            return $this->verifier->verifyIdTokenWithLeeway($token, $leeway);
        }

        return $this->verifier->verifyIdToken($token);
    }
}
