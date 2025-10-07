<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SocialAuthController extends Controller
{
    public function login(SocialLoginRequest $request)
    {
        $data = $request->validated();
        $provider = $data['provider'];
        $token = $data['access_token'];

        // NOTE: This is a stub. In production integrate Socialite or SDK validation.
        // For now we simulate verifying the token and extracting provider_user_id + email.
        $providerUserId = substr(sha1($provider.'|'.$token), 0, 24);
        $email = $request->input('email') ?? ($providerUserId.'@'.$provider.'.oauth');

        $user = User::firstOrCreate(
            ['provider' => $provider, 'provider_user_id' => $providerUserId],
            [
                'name' => ucfirst($provider).' User',
                'email' => $email,
                'password' => Hash::make(bin2hex(random_bytes(8))),
                'type' => 'customer',
            ]
        );

        // Generate JWT token
        \Tymon\JWTAuth\Facades\JWTAuth::factory()->setTTL((int) config('jwt.ttl', 60));
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
        
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Unable to login'], 401);
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]);
    }
}

