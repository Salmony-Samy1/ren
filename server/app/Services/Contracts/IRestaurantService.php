<?php

namespace App\Services\Contracts;
use App\Models\Service;


interface IRestaurantService
{
    public function createRestaurant(array $payload): Service;
    public function updateRestaurant(Service $service, array $payload): Service;
    public function deleteRestaurant(Service $service): void;
}


