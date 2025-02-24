<?php

namespace App\Http\Controllers\WebAuthn;

use http\Client\Curl\User;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;


use function response;

class WebAuthnRegisterController
{
    /**
     * Returns a challenge to be verified by the user device.
     *
     * @param  \Laragear\WebAuthn\Http\Requests\AttestationRequest  $request
     * @return \Illuminate\Contracts\Support\Responsable
     */
    public function options(AttestationRequest $request): Responsable
    {
        return $request
            ->fastRegistration()
//            ->userless()
//            ->allowDuplicates()
            ->toCreate();
    }

    /**
     * Registers a device for further WebAuthn authentication.
     *
     * @param  \Laragear\WebAuthn\Http\Requests\AttestedRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function register(AttestedRequest $request): Response
    {
        $request->save();
        $email = $request->input('email');
        if ($user = \App\Models\User::where('email', $email)->first()) {
            $token = $user->createToken('loginToken')->plainTextToken;
            return response(
                [
                    'success' => true,
                    'username' => $user->email,
                ]
            );
        }

        return response([
            'message' => "Invalid email ",
            'success' => false,
        ]);
    }
    public function createChallenge(AttestationRequest $request)
    {
        return $request->toCreate();
    }
    public function registerDevice(AttestationRequest $request)
    {
        return $request->userless()->toCreate();
    }
}
