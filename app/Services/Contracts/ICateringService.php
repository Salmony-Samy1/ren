<?php

namespace App\Services\Contracts;

use App\Models\Service;

interface ICateringService
{
    public function createCatering(array $payload): Service;
    public function updateCatering(Service $service, array $payload): Service;
    public function deleteCatering(Service $service): void;
}

