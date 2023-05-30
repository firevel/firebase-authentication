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
     * @param  IdTokenVerifier  $verifier
     * @return void
     */
    public function __construct(IdTokenVerifier $verifier)
    {
        $this->verifier = $verifier;
    }

    /**
     * Get User by request claims.
     *
     * @param  Request  $request
     * @return mixed|null
     */
    public function user(Request $request)
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return;
        }

        try {
            $firebaseToken = $this->verifier->verifyIdToken($token);

            return app(config('auth.providers.users.model'))
                ->resolveByClaims($firebaseToken->payload())
                ->setFirebaseAuthenticationToken($token);
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
}
