<?php

use Illuminate\Support\Facades\Route;
use Laragear\WebAuthn\Http\Routes as WebAuthnRoutes;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLoginForm'])->name('login');

Route::post('login', [\App\Http\Controllers\AuthController::class, 'login'])->name('passkey.login');
Route::post('passkeys/authenticate', [\App\Http\Controllers\AuthController::class, 'authenticatePasskey'])->name('passkey.authenticate');
Route::post('/webauthn/prepare-authentication', [\App\Http\Controllers\AuthController::class, 'prepareAuthentication'])->name('prepare-authentication');
Route::post('/webauthn/verify-authentication', [\App\Http\Controllers\AuthController::class, 'verifyAuthentication'])->name('verify-authentication');

\Illuminate\Support\Facades\URL::forceScheme('https');
//\Illuminate\Support\Facades\Auth::routes();
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login'); // Redirect to login after logout
})->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/passkeys', [WebAuthnRegisterController::class, 'index'])->name('passkeys.index');
    Route::get('passkey/list', [\App\Http\Controllers\PasskeyController::class, 'list'])->name('backpack.passkey.list');
    Route::post('passkey/create', [\App\Http\Controllers\PasskeyController::class, 'create'])->name('backpack.passkey.create');
    Route::post('passkey/registration-challenge', [\App\Http\Controllers\PasskeyController::class, 'createRegistrationChallenge'])->name('passkey.register-challenge');

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
