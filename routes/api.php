<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\WebAuthn\Http\Routes as WebAuthnRoutes;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);

Route::post('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::prefix('webauthn')->group(function () {
    Route::post('/login/options', [WebAuthnLoginController::class, 'options']);
    Route::post('/login', [WebAuthnLoginController::class, 'login']);
});
Route::prefix('webauthn')->group(function () {
    Route::post('/register/options', [WebAuthnRegisterController::class, 'options']);
    Route::post('/register', [WebAuthnRegisterController::class, 'register']);
})->middleware('auth:sanctum');
