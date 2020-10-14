<?php

namespace Firevel\FirebaseAuthentication;

use Auth;
use Firebase\Auth\Token\HttpKeyStore;
use Firebase\Auth\Token\Verifier;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class FirebaseAuthenticationServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::viaRequest('firebase', function ($request) {
            return app(FirebaseGuard::class)->user($request);
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Verifier::class, function ($app) {
            $project = config('firebase.project_id', env('GOOGLE_CLOUD_PROJECT'));

            if (empty($project)) {
                throw new \Exception('Missing GOOGLE_CLOUD_PROJECT env variable.');
            }

            $keyStore = new HttpKeyStore(null, cache()->store());

            return new Verifier($project, $keyStore);
        });
    }
}
