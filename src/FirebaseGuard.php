<?php

namespace Firevel\FirebaseAuthentication;

use Firebase\Auth\Token\Verifier;
use Illuminate\Http\Request;

class FirebaseGuard
{
    /**
     * @var Firebase\Auth\Token\Verifier
     */
    protected $verifier;

    /**
     * Constructor.
     *
     * @param Verifier $verifier
     *
     * @return void
     */
    public function __construct(Verifier $verifier)
    {
        $this->verifier = $verifier;
    }

    /**
     * Get User by request claims.
     *
     * @param Request $request
     *
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
                ->setFirebaseAuthenticationToken($token)
                ->resolveByClaims($firebaseToken->getClaims());
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            }

            return;
        }
    }
}
