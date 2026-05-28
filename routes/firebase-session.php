<?php

use Firevel\FirebaseAuthentication\Http\Controllers\FirebaseSessionController;
use Illuminate\Support\Facades\Route;

// Fall back to the default if a blank/"/" prefix is configured, so the login
// route never collapses to POST / and shadows the application root.
$prefix = trim(config('firebase-authentication.session.prefix', 'auth/firebase'), '/') ?: 'auth/firebase';

Route::post($prefix, [FirebaseSessionController::class, 'login'])
    ->name('firebase.session.login');

Route::post($prefix . '/logout', [FirebaseSessionController::class, 'logout'])
    ->name('firebase.session.logout');
