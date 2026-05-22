<?php

use Firevel\FirebaseAuthentication\Http\Controllers\FirebaseSessionController;
use Illuminate\Support\Facades\Route;

$prefix = trim(config('firebase-authentication.session.prefix', 'auth/firebase'), '/');

Route::post($prefix, [FirebaseSessionController::class, 'login'])
    ->name('firebase.session.login');

Route::post($prefix . '/logout', [FirebaseSessionController::class, 'logout'])
    ->name('firebase.session.logout');
