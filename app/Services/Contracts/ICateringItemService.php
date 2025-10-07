<?php

namespace App\Services\Contracts;
use App\Models\Service;


interface ICateringItemService
{
    public function createCateringItem(array $payload): Service;
    public function updateCateringItem(Service $service, array $payload): Service;
    public function deleteCateringItem(Service $service): void;
}
