<?php

namespace Firevel\FirebaseAuthentication\Http\Middleware;

use Closure;

class AddAccessTokenFromCookie
{
    /**
     * Store token from cookie in authorization header.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (empty($request->bearerToken())) {
            $tokenCookie = config('firebase.token_cookie', 'bearer_token');
            if ($request->hasCookie($tokenCookie)) {
                $token = $request->cookie($tokenCookie);
                $request->headers->add(['Authorization' => 'Bearer '.$token]);
            }
        }

        return $next($request);
    }
}
