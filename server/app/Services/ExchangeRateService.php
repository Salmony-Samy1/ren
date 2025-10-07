<?php

namespace App\Services;

use App\Models\ExchangeRate;

class ExchangeRateService
{
    public function getRate(string $from, string $to): ?float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        if ($from === $to) {
            return 1.0;
        }
        $direct = ExchangeRate::where('base_currency',$from)->where('quote_currency',$to)->value('rate');
        if ($direct) { return (float)$direct; }
        $inverse = ExchangeRate::where('base_currency',$to)->where('quote_currency',$from)->value('rate');
        if ($inverse) { return 1.0 / (float)$inverse; }
        return null;
    }

    public function convert(float $amount, string $from, string $to): ?float
    {
        $rate = $this->getRate($from, $to);
        if ($rate === null) { return null; }
        return round($amount * $rate, 2);
    }
}

