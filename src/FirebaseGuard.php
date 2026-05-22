<?php

namespace Firevel\FirebaseAuthentication;

use Illuminate\Http\Request;
use Kreait\Firebase\JWT\IdTokenVerifier;

class FirebaseGuard
{
    /**
     * @var Kreait\Firebase\JWT\IdTokenVerifier
     */
    protected $verifier;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct(IdTokenVerifier $verifier)
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

            $model = $modelClass ?? config('auth.providers.users.model');

            $user = app($model)->resolveByClaims($firebaseToken->payload());

            if ($user === null) {
                return;
            }

            return $user->setFirebaseAuthenticationToken($token);
        } catch (\Exception $e) {
            if ($e instanceof \Kreait\Firebase\JWT\Error\IdTokenVerificationFailed) {
                if (str_contains($e->getMessage(), 'token is expired')) {
                    return;
                }
            }

            if (config('app.debug')) {
                throw $e;
            }

            return;
        }
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
