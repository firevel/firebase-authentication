<?php

use Firevel\FirebaseAuthentication\Http\Controllers\FirebaseSessionController;
use Illuminate\Support\Facades\Route;

// Fall back to the default if a blank/"/" prefix is configured, so the login
// route never collapses to POST / and shadows the application root.
$prefix = trim(config('firebase-authentication.session.prefix', 'auth/firebase'), '/') ?: 'auth/firebase';

// The session is modelled as a resource: POST creates it (login),
// DELETE destroys it (logout). Both act on the bare prefix.
Route::post($prefix, [FirebaseSessionController::class, 'login'])
    ->name('firebase.session.store');

Route::delete($prefix, [FirebaseSessionController::class, 'logout'])
    ->name('firebase.session.destroy');
