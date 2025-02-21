<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use Illuminate\Console\View\Components\Alert;
use Illuminate\Http\Request;
use App\Support\JsonSerializer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorData;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class PasskeyController extends Controller
{
    public function list()
    {
        return view('passkeys'); // Create this view
    }
    public function create(Request $request)
    {
        $user = Auth::user();

        // Validate the passkey response payload
        $validated = $request->validate([
            'id' => ['required', 'string'],
            'rawId' => ['required', 'string'],
            'response' => ['required', 'array'],
            'response.attestationObject' => ['required', 'string'],
            'response.authenticatorData' => ['required', 'string'],
            'response.clientDataJSON' => ['required', 'string'],
            'response.publicKey' => ['required', 'string'],  // Extracting publicKey from response
        ]);
        $rawClientData = $validated['response']['clientDataJSON'];

        // Decode the clientDataJSON into an array
        $clientData = json_decode(Base64UrlSafe::decodeNoPadding($rawClientData), true);

        // Create the CollectedClientData object
        $clientDataJSON = new CollectedClientData($rawClientData, $clientData);

        // Decode the attestationObject (base64) into its raw form
        $rawAttestationObject = base64_decode($validated['response']['attestationObject']);

        // Parse the raw attestation object to extract authenticator data and attestation statement
        // (This parsing would need to be done based on the WebAuthn spec or a relevant library)
        $attestationData = json_decode($rawAttestationObject, true); // This could be a JSON object

        // Create AuthenticatorData from the raw data (custom class to handle this)
        /*$authData = new AuthenticatorData($attestationData['authData']);

        // Create the AttestationStatement from the parsed data (attStmt should be the statement part)
        $attStmt = new AttestationStatement($attestationData['attStmt']);

        // Create the AttestationObject instance
        $attestationObject = new AttestationObject(
            $rawAttestationObject,
            $attStmt,
            $authData
        );*/

        // Optionally, get the transports if provided
        $transports = isset($validated['response']['transports']) ? $validated['response']['transports'] : [];

        // Create the AuthenticatorAttestationResponse object
        $attestationResponse = new AuthenticatorAttestationResponse(
            $clientDataJSON,
           // $attestationObject,
            $transports
        );

        // Create the PublicKeyCredential object with the correct response type
        $publicKeyCredential = new PublicKeyCredential(
            $validated['type'] ?? 'public-key',
            $validated['rawId'],
            $attestationResponse // Pass the instantiated response object
        );
        $publicKeyCredential = new PublicKeyCredential(
            $validated['type'] ?? 'public-key',
            $validated['rawId'],
            $attestationResponse // Pass the instantiated response object
        );

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            return redirect()->guest(route('login'));
        }
        //$publicKeyCredential = JsonSerializer::deserialize($validated, PublicKeyCredential::class);


        try {
            // Retrieve the challenge from the database for this user (previously saved challenge)
            $challenge = Challenge::where('user_id', $user->id)
                ->where('type', 'register')
                ->where('status', 'pending')
                ->firstOrFail();

            // Verify the attestation response with the stored challenge
            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create(
                (new CeremonyStepManagerFactory())->creationCeremony(),
            )->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: json_decode($challenge->challenge, true),
                host: $request->getHost(),
            );

            // Mark the challenge as completed (remove from the pending state)
            $challenge->status = 'completed';
            $challenge->save();

            // Validate public key (e.g., format or any other checks as required)
            $publicKey = $validated['response']['publicKey'];
            if ($this->isInvalidPublicKey($publicKey)) {
                return redirect()->back()->with('error', 'Invalid public key');
            }

            // Store the passkey data
            $result = $user->passkeys()->create([
                'data' => $publicKeyCredentialSource,
            ]);

            if ($result) {
                return redirect()->back()->with('success', 'Passkey created successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to create Passkey');
            }
        } catch (\Throwable $e) {
            // Handle any errors
            return redirect()->back()->with('error', 'An error occurred during passkey registration: ' . $e->getMessage());
        }
    }

// Helper function to validate the public key
    private function isInvalidPublicKey(string $publicKey): bool
    {
        // Perform validation logic for the public key, such as checking its format
        return ! preg_match('/^[A-Za-z0-9+/=]+$/', $publicKey); // Example regex for base64 validation
    }

    /*public function create(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Validate the passkey response (attestation)
        $validated = $request->validate([
            'passkey' => ['required', 'array'],
        ]);

        // Deserialize the public key credential from the request
        $publicKeyCredential = JsonSerializer::deserialize($validated['passkey'], PublicKeyCredential::class);

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            return redirect()->guest(route('login'));
        }

        try {
            // Retrieve the challenge from the database for this user (previously saved challenge)
            $challenge = Challenge::where('user_id', $user->id)
                ->where('type', 'register')
                ->where('status', 'pending')
                ->firstOrFail();

            // Verify the attestation response with the stored challenge
            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create(
                (new CeremonyStepManagerFactory)->creationCeremony(),
            )->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: json_decode($challenge->challenge, true),
                host: $request->getHost(),
            );

            // Mark the challenge as completed (remove from the pending state)
            $challenge->status = 'completed';
            $challenge->save();

            // Store the passkey data
            $result = $user->passkeys()->create([
                'data' => $publicKeyCredentialSource,
            ]);

            if ($result) {
                return redirect()->back()->with('success', 'Passkey created successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to create Passkey');
            }
        } catch (\Throwable $e) {
            // Handle any errors
            return redirect()->back()->with('error', 'An error occurred during passkey registration: ' . $e->getMessage());
        }
    }*/
    public function createRegistrationChallenge(Request $request)
    {
        try {
            $user = Auth::user();
            $userId = \str($user->getAuthIdentifier()) ."87968678687678";

            $rpEntity = new PublicKeyCredentialRpEntity(
                'webauthn',
                config('app.url')   // RP ID (usually your domain)
            );

            // Define the user entity
            $userEntity = new PublicKeyCredentialUserEntity(
                $user->email,       // User name
                $user->id,          // User ID (could be unique DB ID)
                $user->name         // User display name
            );

            // Public key credential parameters (algorithms to support)
            $publicKeyCredParams = [
                new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, -7),
                new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, -257),
                new PublicKeyCredentialParameters(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, -8),
            ];

            // Generate a random challenge
            $challenge = random_bytes(32);
            $encodedChallenge = $this->base64url_encode($challenge);

            // Store the challenge in the database for later verification
            DB::table('challenges')->insert([
                'user_id' => $user->id,
                'challenge' => $encodedChallenge,
                'type' => 'registration',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Define the WebAuthn options
            $options = new PublicKeyCredentialCreationOptions(
                $rpEntity,               // RP entity
                $userEntity,             // User entity
                $encodedChallenge,       // Challenge
                $publicKeyCredParams,    // Supported credential params
                null,                    // Authenticator selection (optional)
                null,                    // Attestation (optional)
                [],                      // Exclude credentials (optional)
                60000                    // Timeout (milliseconds)
            );

            // Return the WebAuthn creation options in JSON format
            $response = [
                "rp" => [
                    "name" => "webauthn",
                    "id" => "fb31-94-203-249-81.ngrok-free.app"
                ],
                "authenticatorSelection" => [
                    "userVerification" => "discouraged"
                ],
                "user" => [
                    "id" =>  $userId,
                    "name" => $user->name,
                    "displayName" =>$user->name
                ],
                "pubKeyCredParams" => [
                    [
                        "type" => "public-key",
                        "alg" => -7
                    ],
                    [
                        "type" => "public-key",
                        "alg" => -257
                    ],
                    [
                        "type" => "public-key",
                        "alg" => -8
                    ]
                ],
                "attestation" => "none",
                "excludeCredentials" => [
                    [
                        "id" => "LScl6dErA8IlsFaUAbcJhxWWM-s",
                        "type" => "public-key"
                    ],
                    [
                        "id" => "xQSI6cbFr9MzlqfAb5AvK49hH3E",
                        "type" => "public-key"
                    ]
                ],
                "timeout" => 60000,
                "challenge" => $encodedChallenge
            ];

            return response()->json($response);
        } catch (\Throwable $e) {
            // Handle errors
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }


}
