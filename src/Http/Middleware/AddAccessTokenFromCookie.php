<?php

namespace Firevel\FirebaseAuthentication\Http\Middleware;

use Closure;

class AddAccessTokenFromCookie
{
    /**
     * Store token from cookie in authorization header.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (empty($request->bearerToken())) {
            $tokenCookie = config('firebase.token_cookie', 'bearer_token');
            $token = $request->cookies->get($tokenCookie);
            if (! empty($token)) {
                $request->headers->add(['Authorization' => 'Bearer '.$token]);
            }
        }

        return $next($request);
    }
}
