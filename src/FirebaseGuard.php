<?php

namespace Firevel\FirebaseAuthentication;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Kreait\Firebase\JWT\IdTokenVerifier;

class FirebaseGuard implements Guard
{
	/**
	 * @var Kreait\Firebase\JWT\IdTokenVerifier
	 */
	protected $verifier;
	protected $user;
	protected $request;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct(IdTokenVerifier $verifier, ?Request $request = null)
	{
		$this->verifier = $verifier;
		$this->request = $request ?: request();
	}

	/**
	 * Get User by request claims.
	 *
	 * @return mixed|null
	 */
	public function user()
	{
		if ($this->user) {
			return $this->user;
		}

		$token = $this->request->bearerToken();
		if (empty($token)) {
			return;
		}

		try {
			$firebaseToken = $this->verifier->verifyIdToken($token);

			$user = app(config('auth.providers.users.model'))
				->resolveByClaims($firebaseToken->payload());

			if ($user) {
				$user->setFirebaseAuthenticationToken($token);
			}

			return $this->user = $user;
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

	public function validate(array $credentials = [])
	{
		// not used - Laravels sessionless guards skip this
		return (bool) $this->user();
	}

	public function id()
	{
		return $this->user()?->getAuthIdentifier();
	}

	public function check()
	{
		return !is_null($this->user());
	}

	public function guest()
	{
		return is_null($this->user());
	}

	public function setUser(Authenticatable $user)
	{
		$this->user = $user;
		return $this;
	}

	public function hasUser()
	{
		return !is_null($this->user);
	}
}