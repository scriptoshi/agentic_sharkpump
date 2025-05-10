<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $providerUser = Socialite::driver($provider)->user();

            $user = User::updateOrCreate(
                ['email' => $providerUser->email],
                [
                    'name' => $providerUser->name,
                    'provider_id' => $providerUser->id,
                    'provider_name' => $provider,
                    'password' => bcrypt(Str::random(24))
                ]
            );

            Auth::login($user);

            return redirect()->intended(route('dashboard', ['launchpad' => \App\Route::launchpad()]));
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Something went wrong with Google login');
        }
    }
}
