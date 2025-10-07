<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialiteAuthController extends Controller
{
    public function redirectGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callbackGoogle()
    {
        $providerUser = Socialite::driver('google')->stateless()->user();
        return $this->loginOrRegister('google', $providerUser->getId(), $providerUser->getEmail(), $providerUser->getName());
    }

    public function redirectApple()
    {
        return Socialite::driver('apple')->stateless()->redirect();
    }

    public function callbackApple()
    {
        $providerUser = Socialite::driver('apple')->stateless()->user();
        return $this->loginOrRegister('apple', $providerUser->getId(), $providerUser->getEmail(), $providerUser->getName());
    }

    private function loginOrRegister(string $provider, string $providerId, ?string $email, ?string $name)
    {
        $user = User::firstOrCreate(
            ['provider' => $provider, 'provider_user_id' => $providerId],
            [
                'name' => $name ?: ucfirst($provider).' User',
                'email' => $email ?: ($providerId.'@'.$provider.'.oauth'),
                'password' => Hash::make(bin2hex(random_bytes(8))),
                'type' => 'customer',
            ]
        );

        if (! $token = auth('api')->login($user)) {
            return response()->json(['success' => false, 'message' => 'Unable to login'], 401);
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]);
    }
}

