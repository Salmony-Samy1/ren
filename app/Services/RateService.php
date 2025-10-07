<?php

namespace App\Services;

use App\Models\Service;

class RateService
{
    public function __construct()
    {
    }

    public function add(int $service_id, int $user_id, float $rate, string $comment = '')
    {
        try {
            $service = Service::findOrFail($service_id);
            $service->rate($rate, $comment, $user_id);
            return true;
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }
}
