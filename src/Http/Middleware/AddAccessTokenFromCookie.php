<?php

namespace Firevel\FirebaseAuthentication\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AddAccessTokenFromCookie
{
    /**
     * Store token from cookie in authorization header.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (empty($request->bearerToken())) {
            $tokenCookie = config('firebase-authentication.token_cookie')
                ?? config('firebase.token_cookie')
                ?? 'bearer_token';
            $token = $request->cookies->get($tokenCookie);
            if (! empty($token)) {
                $request->headers->add(['Authorization' => 'Bearer ' . $token]);
            }
        }

        return $next($request);
    }
}
