<?php

namespace App\Services\Currency;

class CurrencyResolver
{
    public function currencyForCountry(string $countryCode): string
    {
        return strtoupper($countryCode) === 'BH' ? 'BHD' : 'SAR';
    }
}

