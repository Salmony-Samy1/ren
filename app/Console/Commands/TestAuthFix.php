<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

class TestAuthFix extends Command
{
    protected $signature = 'auth:test-fix';
    protected $description = 'Test authentication fixes';

    public function handle()
    {
        $this->info('Testing Authentication Fixes...');
        
        // Test 1: Check guard configuration
        $this->info('1. Checking guard configuration...');
        $apiGuard = config('auth.guards.api');
        $this->line("API Guard Driver: {$apiGuard['driver']}");
        
        if ($apiGuard['driver'] !== 'jwt') {
            $this->error('❌ API guard is not set to JWT!');
            return 1;
        }
        $this->info('✅ API guard is correctly set to JWT');
        
        // Test 2: Check JWT configuration
        $this->info('2. Checking JWT configuration...');
        $jwtSecret = config('jwt.secret');
        if (empty($jwtSecret)) {
            $this->error('❌ JWT_SECRET is not set!');
            return 1;
        }
        $this->info('✅ JWT_SECRET is configured');
        
        // Test 3: Test token generation
        $this->info('3. Testing token generation...');
        $user = User::first();
        if (!$user) {
            $this->error('❌ No users found in database');
            return 1;
        }
        
        try {
            $token = JWTAuth::fromUser($user);
            $this->info('✅ Token generated successfully');
            
            // Test 4: Test token authentication
            $this->info('4. Testing token authentication...');
            $authenticatedUser = JWTAuth::setToken($token)->authenticate();
            
            if ($authenticatedUser && $authenticatedUser->id === $user->id) {
                $this->info('✅ Token authentication successful');
            } else {
                $this->error('❌ Token authentication failed');
                return 1;
            }
            
            // Test 5: Test Auth::user() with API guard
            $this->info('5. Testing Auth::user() with API guard...');
            Auth::shouldUse('api');
            JWTAuth::setToken($token);
            $authUser = Auth::user();
            
            if ($authUser && $authUser->id === $user->id) {
                $this->info('✅ Auth::user() returns correct user');
            } else {
                $this->error('❌ Auth::user() failed');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return 1;
        }
        
        $this->info('🎉 All authentication tests passed!');
        return 0;
    }
}

