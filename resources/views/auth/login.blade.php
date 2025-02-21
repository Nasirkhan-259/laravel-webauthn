@extends('layouts.app') {{-- Ensure this extends your layout --}}

@section('content')

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">{{ __('Login') }}</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <!-- Username and password form -->
                            <input type="email" name="email" required placeholder="Email">
                            <input type="password" name="password" required placeholder="Password">
                            <button type="submit" class="btn btn-primary">Login with Password</button>
                        </form>

                        <!-- Option to login with passkey -->
                        @if ($valid_passkey_challenge)
                            <button type="button" id="btn-passkey-auth" class="btn btn-success">
                                Sign in with Passkey
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('btn-passkey-auth').addEventListener('click', function () {
            // Start passkey authentication process
            fetch('/webauthn/prepare-authentication', { // Step 1: Request authentication challenge from server
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
            })
                .then(response => response.json())
                .then(data => {
                    if (data.challenge) {
                        const publicKeyCredentialRequestOptions = {
                            challenge: Uint8Array.from(atob(data.challenge), c => c.charCodeAt(0)),
                            allowCredentials: data.allowCredentials.map(cred => ({
                                id: Uint8Array.from(atob(cred.id), c => c.charCodeAt(0)),
                                type: cred.type,
                                transports: cred.transports,
                            })),
                            timeout: data.timeout,
                            userVerification: data.userVerification,
                        };

                        // Step 2: Authenticate user with WebAuthn
                        navigator.credentials.get({
                            publicKey: publicKeyCredentialRequestOptions
                        })
                            .then((assertion) => {
                                // Pass the assertion to the server for validation
                                const credential = {
                                    id: assertion.id,
                                    rawId: btoa(String.fromCharCode(...new Uint8Array(assertion.rawId))),
                                    response: {
                                        clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(assertion.response.clientDataJSON))),
                                        authenticatorData: btoa(String.fromCharCode(...new Uint8Array(assertion.response.authenticatorData))),
                                        signature: btoa(String.fromCharCode(...new Uint8Array(assertion.response.signature))),
                                        userHandle: assertion.response.userHandle ? btoa(String.fromCharCode(...new Uint8Array(assertion.response.userHandle))) : null,
                                    },
                                };

                                // Send the credential to the server for verification
                                fetch('/webauthn/verify-authentication', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({ credential })
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            window.location.href = '/home';  // Redirect to the user dashboard
                                        } else {
                                            alert('Passkey authentication failed.');
                                        }
                                    })
                                    .catch(err => {
                                        console.error('Authentication failed:', err);
                                        alert('An error occurred while authenticating.');
                                    });
                            })
                            .catch(err => {
                                console.error('WebAuthn authentication failed:', err);
                                alert('An error occurred during passkey authentication.');
                            });
                    } else {
                        alert('No passkey challenge available.');
                    }
                })
                .catch(err => {
                    console.error('Failed to prepare authentication:', err);
                    alert('An error occurred while preparing passkey authentication.');
                });
        });
    </script>
@endsection


