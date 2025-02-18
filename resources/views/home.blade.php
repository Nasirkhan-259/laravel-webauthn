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
                        <input type="email" id="user-email" placeholder="User Email">
                        <button id="register-passkey-btn">Register Passkey</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const attestOptionsConfig = {
        path: "/webauthn/register/options"
    };
    const attestConfig = {
        path: "/webauthn/register",
        body: {
            email: document.getElementById("user-email").value,
        }
    };
    // Wait until the DOM is ready
    document.addEventListener('DOMContentLoaded', async () => {
        // Check if the browser supports WebAuthn
        if (Webpass.isUnsupported()) {
            alert("Your browser doesn't support WebAuthn.");
            return;
        }

        document.getElementById('register-passkey-btn').addEventListener('click', async () => {
            const { data, success, error } = await Webpass.attest(attestOptionsConfig, attestConfig);

            if (success) {
                // On success, you might want to generate a token on the backend and return it.
                // For now, we'll simply redirect:
                window.location.replace("/home");
            } else {
                console.error("Attestation error:", error);
                alert("Passkey registration failed.");
            }
        });
    });
</script>
@endsection
