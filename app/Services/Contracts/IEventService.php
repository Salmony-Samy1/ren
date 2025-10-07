<?php

namespace App\Services\Contracts;
use App\Models\Service;


interface IEventService
{
    public function createEvent(array $payload): Service;
    public function updateEvent(Service $service, array $payload): Service;
    public function deleteEvent(Service $service): void;
}


