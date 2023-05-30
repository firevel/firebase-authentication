<?php

namespace Firevel\FirebaseAuthentication;

use Auth;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Kreait\Firebase\JWT\IdTokenVerifier;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

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
        $this->app->singleton(IdTokenVerifier::class, function ($app) {
            $project = config('firebase.project_id', env('GOOGLE_CLOUD_PROJECT'));

            if (empty($project)) {
                throw new \Exception('Missing GOOGLE_CLOUD_PROJECT env variable.');
            }
            $cache = new FilesystemAdapter(
                // a string used as the subdirectory of the root cache directory, where cache
                // items will be stored
                $namespace = 'firebase-token-cache'
            );

            return IdTokenVerifier::createWithProjectIdAndCache($project, $cache);
        });
    }
}
