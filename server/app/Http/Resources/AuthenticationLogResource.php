<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticationLogResource extends JsonResource
{
    /** @var \App\Models\AuthenticationLog */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'ip_address' => (string) $this->ip_address,
            'user_agent' => (string) $this->user_agent,
            'device_type' => $this->detectDeviceType($this->user_agent),
            'os' => $this->detectOs($this->user_agent),
            'login_at' => optional($this->login_at)?->toIso8601String(),
            'logout_at' => optional($this->logout_at)?->toIso8601String(),
            'login_successful' => (bool) $this->login_successful,
            'location' => $this->location,
        ];
    }

    private function detectDeviceType(?string $ua): ?string
    {
        $ua = strtolower((string) $ua);
        return match (true) {
            str_contains($ua, 'mobile') => 'mobile',
            str_contains($ua, 'tablet') => 'tablet',
            str_contains($ua, 'windows') || str_contains($ua, 'macintosh') || str_contains($ua, 'linux') => 'desktop',
            default => null,
        };
    }

    private function detectOs(?string $ua): ?string
    {
        $ua = strtolower((string) $ua);
        return match (true) {
            str_contains($ua, 'windows') => 'windows',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macos',
            str_contains($ua, 'android') => 'android',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ios') => 'ios',
            str_contains($ua, 'linux') => 'linux',
            default => null,
        };
    }
}

