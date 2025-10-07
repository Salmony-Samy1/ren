<?php

namespace App\Services\Geo;

class CountryResolver
{
    /**
     * Best-effort country detection for SA/BH.
     * Priority: explicit keywords in address, else simple lat/lng bounding boxes.
     * Returns 'SA' or 'BH'. Defaults to 'SA' if uncertain.
     */
    public function resolve(?string $address, ?float $latitude, ?float $longitude): string
    {
        $addr = strtolower($address ?? '');
        if ($addr) {
            if (str_contains($addr, 'bahrain') || str_contains($addr, 'manama') || str_contains($addr, 'bahrein')) {
                return 'BH';
            }
            if (str_contains($addr, 'saudi') || str_contains($addr, 'riyadh') || str_contains($addr, 'jeddah') || str_contains($addr, 'saudi arabia') || str_contains($addr, 'ksa')) {
                return 'SA';
            }
        }

        if ($latitude !== null && $longitude !== null) {
            // Bahrain rough bounding box
            $isBahrain = ($latitude >= 25.45 && $latitude <= 26.60) && ($longitude >= 50.30 && $longitude <= 50.95);
            if ($isBahrain) {
                return 'BH';
            }
            // Otherwise assume SA for our limited scope
            return 'SA';
        }

        return 'SA';
    }
}

