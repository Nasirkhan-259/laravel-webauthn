@extends('layouts.app')

@section('content')
<div class="col-lg-8 mb-4">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Manage Passkeys</h3>
        </div>

        <div class="card-body backpack-profile-form bold-labels">
            {{-- REGISTERED PASSKEYS LIST --}}
            @if(count($passkeys ?? []) > 0)
                <div class="table-responsive">
                    <table class="table" style="width:100%">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($passkeys as $passkey)
                            <tr>
                                <td>{{ $passkey->name }}</td>
                                <td>{{ $passkey->created_at->diffForHumans() }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger">
                                        <i class="la la-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    No passkeys registered yet.
                </div>
            @endif

            {{-- REGISTER NEW PASSKEY --}}
            <form id="passkey-form" class="form mt-4" action="{{ route('backpack.passkey.create') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="required">Name</label>
                        <input required class="form-control" type="text" name="name" value="{{ old('name') }}"
                               placeholder="Enter a name for this passkey" id="user-name">
                    </div>
                </div>

                <!-- Hidden field to store the generated passkey response -->
                <input type="hidden" id="passkey" name="passkey" value="">

                <div class="mt-3">
                    <button type="submit" id="register-passkey-btn" class="btn btn-success">
                        <i class="la la-key"></i> Register New Passkey
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // Configuration for the WebAuthn options and registration
    const attestOptionsConfig = {
        path: "/passkey/registration-challenge",  // Endpoint to get options
    };
    const attestConfig = {
        path: "passkey/create",  // Endpoint to register after getting the options
        body: {
            email: document.getElementById("user-name").value,  // User email or other relevant data
        }
    };

    // Wait until the DOM is ready
    document.addEventListener('DOMContentLoaded', async () => {
        // Check if the browser supports WebAuthn
        if (Webpass.isUnsupported()) {
            alert("Your browser doesn't support WebAuthn.");
            return;
        }

        // Handle the registration button click event
        document.getElementById('register-passkey-btn').addEventListener('click', async (e)=> {
            e.preventDefault();
            try {
                // Start the attestation process
                const data = await Webpass.attest(attestOptionsConfig, attestConfig);
                console.log(data)
                /*if (success) {
                    // On success, you might want to generate a token on the backend and return it
                    // For now, we'll redirect to the home page
                    //window.location.replace("/home");
                } else {
                    console.error("Attestation error:", error);
                    alert("Passkey registration failed.");
                }*/
            } catch (err) {
                console.error('Error during attestation:', err);
                alert('Error during passkey registration: ' + err.message);
            }
        });
    });
</script>

@endsection
