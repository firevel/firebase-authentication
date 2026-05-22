<?php

namespace Firevel\FirebaseAuthentication\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;

class FirebaseSessionController extends Controller
{
    public function __construct(
        protected IdTokenVerifier $verifier
    ) {}

    /**
     * Exchange a Firebase ID token for an authenticated Laravel session.
     */
    public function login(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return response()->json(['error' => 'Missing Firebase ID token.'], 401);
        }

        try {
            $firebaseToken = $this->verifyToken($token);
        } catch (IdTokenVerificationFailed $e) {
            return response()->json(['error' => 'Invalid Firebase ID token.'], 401);
        }

        $guard = $this->guardName();
        $providerName = config("auth.guards.{$guard}.provider", 'users');
        $modelClass = config("auth.providers.{$providerName}.model");

        if (empty($modelClass)) {
            return response()->json(['error' => 'No user provider configured for guard [' . $guard . '].'], 500);
        }

        $user = app($modelClass)->resolveByClaims($firebaseToken->payload());

        if ($user === null) {
            return response()->json(['error' => 'No matching user account.'], 401);
        }

        Auth::guard($guard)->login($user);
        $request->session()->regenerate();

        return response()->json(['authenticated' => true]);
    }

    /**
     * Destroy the current Laravel session.
     */
    public function logout(Request $request): JsonResponse
    {
        $guard = $this->guardName();

        Auth::guard($guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['authenticated' => false]);
    }

    protected function guardName(): string
    {
        return config('firebase-authentication.session.guard', 'web');
    }

    /**
     * Verify the Firebase ID token, honoring the configured clock-skew
     * leeway. Override to further customize verification.
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
