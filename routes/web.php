<?php

use Illuminate\Support\Facades\Route;
use Laragear\WebAuthn\Http\Routes as WebAuthnRoutes;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
Route::get('/', function () {
    return view('welcome');
});

\Illuminate\Support\Facades\Auth::routes();
Route::middleware('auth')->group(function () {
    Route::get('/passkeys', [WebAuthnRegisterController::class, 'index'])->name('passkeys.index');
    Route::prefix('webauthn')->group(function () {
        Route::post('/register/options', [WebAuthnRegisterController::class, 'options']);
        Route::post('/register', [WebAuthnRegisterController::class, 'register']);
    });

});
Route::prefix('webauthn')->group(function () {
    Route::post('/login/options', [WebAuthnLoginController::class, 'options']);
    Route::post('/login', [WebAuthnLoginController::class, 'login']);
});
WebAuthnRoutes::register(
    attest: 'auth/register',
    assert: 'auth/login'
);
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
