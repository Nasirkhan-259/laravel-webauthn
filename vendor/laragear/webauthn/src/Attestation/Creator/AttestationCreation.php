<?php

namespace Laragear\WebAuthn\Attestation\Creator;

use Laragear\WebAuthn\Challenge\Challenge;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\Enums\ResidentKey;
use Laragear\WebAuthn\Enums\UserVerification;
use Laragear\WebAuthn\JsonTransport;

class AttestationCreation
{
    /**
     * Create a new Attestation Creation instance.
     */
    public function __construct(
        public ?WebAuthnAuthenticatable $user,
        public ?ResidentKey $residentKey = null,
        public ?UserVerification $userVerification = null,
        public ?Challenge $challenge = null,
        public JsonTransport $json = new JsonTransport(),
        public bool $uniqueCredentials = true,
    ) {
        //
    }
}
