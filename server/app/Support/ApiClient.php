<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class ApiClient
{
    public static function get(string $path, array $query = [])
    {
        $token = session('auth_token');
        return Http::acceptJson()
            ->when($token, fn($r) => $r->withToken($token))
            ->get(url($path), $query);
    }

    public static function post(string $path, array $payload = [])
    {
        $token = session('auth_token');
        return Http::acceptJson()
            ->when($token, fn($r) => $r->withToken($token))
            ->post(url($path), $payload);
    }

    public static function put(string $path, array $payload = [])
    {
        $token = session('auth_token');
        return Http::acceptJson()
            ->when($token, fn($r) => $r->withToken($token))
            ->put(url($path), $payload);
    }

    public static function delete(string $path)
    {
        $token = session('auth_token');
        return Http::acceptJson()
            ->when($token, fn($r) => $r->withToken($token))
            ->delete(url($path));
    }
}

