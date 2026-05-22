<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Http\Middleware\AddAccessTokenFromCookie;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use Illuminate\Http\Request;

class AddAccessTokenFromCookieTest extends TestCase
{
    /** @test */
    public function it_adds_authorization_header_from_bearer_token_cookie()
    {
        $middleware = new AddAccessTokenFromCookie;
        $token = 'jwt-token-from-cookie';

        $request = Request::create('/web/test', 'GET');
        $request->cookies->set('bearer_token', $token);

        $middleware->handle($request, function ($req) use ($token) {
            $this->assertEquals('Bearer '.$token, $req->header('Authorization'));
            $this->assertEquals($token, $req->bearerToken());

            return response('OK');
        });
    }

    /** @test */
    public function it_uses_custom_cookie_name_from_config()
    {
        config(['firebase.token_cookie' => 'custom_token']);

        $middleware = new AddAccessTokenFromCookie;
        $token = 'jwt-token-from-custom-cookie';

        $request = Request::create('/web/test', 'GET');
        $request->cookies->set('custom_token', $token);

        $middleware->handle($request, function ($req) use ($token) {
            $this->assertEquals('Bearer '.$token, $req->header('Authorization'));
            $this->assertEquals($token, $req->bearerToken());

            return response('OK');
        });
    }

    /** @test */
    public function it_does_not_override_existing_bearer_token()
    {
        $middleware = new AddAccessTokenFromCookie;
        $headerToken = 'jwt-token-from-header';
        $cookieToken = 'jwt-token-from-cookie';

        $request = Request::create('/web/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$headerToken);
        $request->cookies->set('bearer_token', $cookieToken);

        $middleware->handle($request, function ($req) use ($headerToken, $cookieToken) {
            // Should keep the header token, not replace with cookie token
            $this->assertEquals('Bearer '.$headerToken, $req->header('Authorization'));
            $this->assertEquals($headerToken, $req->bearerToken());
            $this->assertNotEquals($cookieToken, $req->bearerToken());

            return response('OK');
        });
    }

    /** @test */
    public function it_does_nothing_when_no_cookie_is_present()
    {
        $middleware = new AddAccessTokenFromCookie;

        $request = Request::create('/web/test', 'GET');

        $middleware->handle($request, function ($req) {
            $this->assertNull($req->header('Authorization'));
            $this->assertNull($req->bearerToken());

            return response('OK');
        });
    }

    /** @test */
    public function it_does_nothing_when_bearer_token_already_exists_and_no_cookie()
    {
        $middleware = new AddAccessTokenFromCookie;
        $headerToken = 'jwt-token-from-header';

        $request = Request::create('/web/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$headerToken);

        $middleware->handle($request, function ($req) use ($headerToken) {
            $this->assertEquals('Bearer '.$headerToken, $req->header('Authorization'));
            $this->assertEquals($headerToken, $req->bearerToken());

            return response('OK');
        });
    }

    /** @test */
    public function it_allows_request_to_continue()
    {
        $middleware = new AddAccessTokenFromCookie;
        $token = 'jwt-token-from-cookie';

        $request = Request::create('/web/test', 'GET');
        $request->cookies->set('bearer_token', $token);

        $response = $middleware->handle($request, function ($req) {
            return response('Request processed', 200);
        });

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Request processed', $response->getContent());
    }

    /** @test */
    public function it_works_with_empty_bearer_token_cookie()
    {
        $middleware = new AddAccessTokenFromCookie;

        $request = Request::create('/web/test', 'GET');
        $request->cookies->set('bearer_token', '');

        $middleware->handle($request, function ($req) {
            // Empty cookie should not add an Authorization header at all
            $this->assertNull($req->header('Authorization'));

            return response('OK');
        });
    }

    /** @test */
    public function it_handles_multiple_requests_independently()
    {
        $middleware = new AddAccessTokenFromCookie;

        // First request with cookie
        $request1 = Request::create('/web/test1', 'GET');
        $request1->cookies->set('bearer_token', 'token-1');

        $middleware->handle($request1, function ($req) {
            $this->assertEquals('token-1', $req->bearerToken());

            return response('OK');
        });

        // Second request without cookie
        $request2 = Request::create('/web/test2', 'GET');

        $middleware->handle($request2, function ($req) {
            $this->assertNull($req->bearerToken());

            return response('OK');
        });

        // Third request with different cookie
        $request3 = Request::create('/web/test3', 'GET');
        $request3->cookies->set('bearer_token', 'token-3');

        $middleware->handle($request3, function ($req) {
            $this->assertEquals('token-3', $req->bearerToken());

            return response('OK');
        });
    }
}
