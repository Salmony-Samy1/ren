<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->validated();
        $credentials['type'] = 'admin';
        $result = $this->authService->login($credentials);
        if ($result['status']) {
            $payload = array_merge(
                (new UserResource($result['user']))->resolve(),
                ['token' => $result['token'], 'remember' => (bool)($result['remember'] ?? false), 'ttl' => $result['ttl'] ?? null]
            );
            $response = format_response(true, __('User logged in successfully'), $payload);

            if (!empty($credentials['remember']) && ($result['remember'] ?? false)) {
                $cookieName = env('AUTH_TOKEN_COOKIE', 'auth_token');
                $minutes = (int) ($result['ttl'] ?? 0);
                $cookie = cookie(
                    name: $cookieName,
                    value: $result['token'],
                    minutes: $minutes,
                    path: '/',
                    domain: config('session.domain'),
                    secure: (bool) config('session.secure', true),
                    httpOnly: (bool) config('session.http_only', true),
                    sameSite: config('session.same_site', 'lax')
                );
                $response->headers->setCookie($cookie);
            }

            return $response;
        }
        return format_response(false, __('Wrong credentials'), code: 401);
    }

    public function refreshToken()
    {
        $result = $this->authService->refreshToken();
        return format_response(true, __('Token refreshed successfully'), ['token' => $result]);
    }

    public function logout()
    {
        $this->authService->logout();

        // Clear auth cookie if set
        $cookieName = env('AUTH_TOKEN_COOKIE', 'auth_token');
        cookie()->queue(cookie(
            name: $cookieName,
            value: '',
            minutes: -1,
            path: '/',
            domain: config('session.domain'),
            secure: (bool) config('session.secure', true),
            httpOnly: (bool) config('session.http_only', true),
            sameSite: config('session.same_site', 'lax')
        ));

        return format_response(true, __('User logged out successfully'));
    }
}
