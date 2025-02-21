<?php

namespace App\Http\Controllers;

use App\Models\Passkey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use App\Support\JsonSerializer;
use Illuminate\Validation\ValidationException;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredential;
class AuthController extends Controller
{
    /*public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($validated)) {
            $user = Auth::user();
            $token = $user->createToken('laravel')->plainTextToken;
            return response()->json(['token' => $token]);
        }

        return response()->json(['message' => 'Invalid credentials.'], 401);
    }*/
    public function showLoginForm()
    {
        $data['title'] = __('Login');
        // For now, just return true for valid passkey check
        // This will be checked later during the authentication phase
        $data['valid_passkey_challenge'] = true; // Replace with actual logic once passkey is chosen
        return view('auth.login', $data);
    }
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'sometimes|required|string', // Password is optional if using passkey
            'credential' => 'sometimes|json', // Passkey credential data (optional)
        ]);

        // If the user chose passkey, validate passkey credentials
        if ($request->has('credential')) {
            return $this->authenticateWithPasskey($request, $validated);
        }

        // If password is provided, authenticate with traditional method
        if (Auth::attempt(['email' => $validated['email'], 'password' => $validated['password'] ?? ''])) {
            $user = Auth::user();
            $token = $user->createToken('laravel')->plainTextToken;
            return response()->json(['token' => $token]);
        }

        return response()->json(['message' => 'Invalid credentials.'], 401);
    }
    public function authenticateWithPasskey(Request $request, array $validated)
    {
        // Handle the passkey verification logic here
        $publicKeyCredential = JsonSerializer::deserialize($validated['credential'], PublicKeyCredential::class);

        // Validate passkey against stored credentials in the DB
        $passkey = Passkey::where('user_id', $validated['email'])->first();

        if (!$passkey) {
            return response()->json(['message' => 'Invalid passkey credentials.'], 401);
        }
        $this->guard()->loginUsingId($passkey->user_id);

        return redirect()->intended($this->redirectPath());
    }
    public function authenticatePasskey(Request $request)
    {
        $validated = $request->validate([
            'answer' => ['required', 'json'],
        ]);

        // Deserialize the answer from the request
        $publicKeyCredential = JsonSerializer::deserialize($validated['answer'], PublicKeyCredential::class);

        if (! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return redirect()->back('login');
        }

        $passkey = Passkey::firstWhere('credential_id', $publicKeyCredential->rawId);

        if (! $passkey) {
            throw ValidationException::withMessages(['email' => 'The passkey is invalid.']);
        }

        try {
            $publicKeyCredentialSource = AuthenticatorAssertionResponseValidator::create(
                (new CeremonyStepManagerFactory)->requestCeremony()
            )->check(
                publicKeyCredentialSource: $passkey->data,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: Session::get('passkey_authentication_options'),
                host: $request->getHost(),
                userHandle: null,
            );
        } catch (\Throwable $e) {
          dd($e->getMessage());
        }

        $passkey->update(['data' => $publicKeyCredentialSource]);

        // Login the user
        $this->guard()->loginUsingId($passkey->user_id);

        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath());
    }
    public function prepareAuthentication(Request $request)
    {
        dd($request);
        // Get the user's stored passkeys (public credentials)
        $user = Auth::user();
        $credentials = $user->passkeys;

        if ($credentials->isEmpty()) {
            return response()->json(['message' => 'No passkeys available.'], 400);
        }

        // Generate a challenge for WebAuthn authentication
        $challenge = random_bytes(32);  // Random challenge (can be hashed, etc.)
        $this->storeChallenge($user, $challenge); // Store it temporarily for later verification

        // Return the challenge and other WebAuthn options
        return response()->json([
            'challenge' => base64_encode($challenge),
            'allowCredentials' => $credentials->map(function ($cred) {
                return [
                    'id' => base64_encode($cred->credential_id),
                    'type' => 'public-key',
                    'transports' => ['usb', 'nfc', 'ble', 'hybrid'], // Adjust based on your configuration
                ];
            }),
            'timeout' => 60000,  // 1 minute timeout
            'userVerification' => 'preferred',
        ]);
    }
    public function verifyAuthentication(Request $request)
    {
        $validated = $request->validate([
            'credential' => 'required|array',
        ]);

        $credential = $validated['credential'];

        // Validate the assertion using WebAuthn
        $user = $this->verifyCredentialAssertion($credential);  // This function verifies the assertion

        if ($user) {
            Auth::login($user);  // Log the user in
            return response()->json(['success' => true]);
        }

        return response()->json(['message' => 'Authentication failed.'], 401);
    }
    public function storeChallenge($user, $challenge)
    {
        // Store the challenge in the session with a unique identifier (user ID)
        session(['webauthn_challenge_' . $user->id => base64_encode($challenge)]);
    }
}
