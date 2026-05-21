<?php

namespace Firevel\FirebaseAuthentication;

use Auth;
use Illuminate\Auth\RequestGuard;
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

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/prepare_users_table_for_firebase.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_prepare_users_table_for_firebase.php'),
            ], 'firebase-authentication-migrations');
        }

        Auth::extend('firebase', function ($app, $name, array $config) {
            $provider = $config['provider'] ?? 'users';
            $model = config("auth.providers.{$provider}.model");

            return new RequestGuard(function ($request) use ($model) {
                return app(FirebaseGuard::class)->user($request, $model);
            }, $app['request']);
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
