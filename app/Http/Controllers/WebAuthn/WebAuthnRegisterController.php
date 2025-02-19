<?php

namespace App\Http\Controllers\WebAuthn;

use http\Client\Curl\User;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
        return response()->noContent();
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
