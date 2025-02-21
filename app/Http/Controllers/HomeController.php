<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\JsonSerializer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function getAccountInfoForm()
    {
        $this->data['title'] = trans('backpack::base.my_account');
        $this->data['user'] = $this->guard()->user();

        $this->data['passkeys'] = $this->guard()->user()->passkeys()->select(['id', 'name', 'created_at'])->get();

        Session::flash('passkey_register_options', $this->getRegisterOptions());

        return view(backpack_view('my_account'), $this->data);
    }
    /**
     * Generate WebAuthn registration options for credential creation.
     * Necessary data including relying party details, user information, and a random challenge.
     *
     * @throws InvalidDataException
     */
    private function getRegisterOptions()
    {
        return new PublicKeyCredentialCreationOptions(
            rp: new PublicKeyCredentialRpEntity(
                name: config('app.name'),
                id: parse_url(config('app.url'), PHP_URL_HOST),
            ),
            user: new PublicKeyCredentialUserEntity(
                name:  Auth::user()->name,
                id: Auth::user()->id ,
                displayName: Auth::user()->name,
            ),
            challenge: Str::random(),
        );
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        Session::put('passkey_register_options', $this->getRegisterOptions());
        return view('home');

    }
}
