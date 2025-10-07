<?php

namespace App\Services\ServiceManagement\Contracts;

use App\Models\Service;

interface ServiceUpdateStrategy
{
    /**
     * Update a service of a specific type
     *
     * @param Service $service
     * @param array $data
     * @return Service
     */
    public function update(Service $service, array $data): Service;

    /**
     * Validate data for this service type
     *
     * @param array $data
     * @return array Array of validation errors (empty if valid)
     */
    public function validateData(array $data): array;

    /**
     * Get the service type this strategy handles
     *
     * @return string
     */
    public function getServiceType(): string;
}

