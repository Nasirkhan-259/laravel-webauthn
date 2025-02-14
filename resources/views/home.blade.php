@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ __('You are logged in!') }}
                        <button id="register-passkey-btn">Register Passkey</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Wait until the DOM is ready
    document.addEventListener('DOMContentLoaded', async () => {
        // Check if the browser supports WebAuthn
        if (Webpass.isUnsupported()) {
            alert("Your browser doesn't support WebAuthn.");
            return;
        }

        // Add a click handler for passkey registration
        document.getElementById('register-passkey-btn').addEventListener('click', async () => {
            // Call the attestation helper
            const { success, error } = await Webpass.attest(
                "/webauthn/register/options",  // Endpoint to fetch registration options
                "/webauthn/register"             // Endpoint to process the attestation response
            );

            if (success) {
                // On success, redirect or show a success message.
                window.location.replace("/home");
            } else {
                console.error("Attestation error:", error);
                alert("Passkey registration failed.");
            }
        });
    });
</script>
@endsection
