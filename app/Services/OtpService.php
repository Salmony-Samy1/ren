<?php

namespace App\Services;

use App\Enums\SettingKeys;
use Ichtrojan\Otp\Otp;
use Ichtrojan\Otp\Models\Otp as OtpModel;

class OtpService
{
    public static function generateOtp(string $identifier)
    {
        // Support static OTP for tests when configured
        $gateway = get_setting('otp_gateway');
        if ($gateway === 'static') {
            return ['token' => '12345'];
        }
        // Default: generate a numeric OTP (e.g., 6 digits)
        return (new Otp())->generate($identifier, 'numeric', 6);
    }

    public static function validate(string $identifier, string $otp)
    {
        // Support static OTP for tests
        $gateway = get_setting('otp_gateway');
        if ($gateway === 'static') {
            return $otp === '12345';
        }
        // Custom validation to avoid type issues and ensure boolean result
        $row = OtpModel::where('identifier', $identifier)->where('token', $otp)->first();
        if (!$row) {
            return false;
        }
        if (!($row->valid ?? false)) {
            // Invalidate just in case
            try { $row->update(['valid' => false]); } catch (\Throwable $e) {}
            return false;
        }
        $validMinutes = (int) ($row->validity ?? 10);
        $validUntil = $row->created_at?->copy()->addMinutes($validMinutes);
        if (!$validUntil || now()->greaterThan($validUntil)) {
            try { $row->update(['valid' => false]); } catch (\Throwable $e) {}
            return false;
        }
        try { $row->update(['valid' => false]); } catch (\Throwable $e) {}
        return true;
    }
}
