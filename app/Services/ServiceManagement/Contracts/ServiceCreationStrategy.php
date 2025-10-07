<?php

namespace App\Services\ServiceManagement\Contracts;

use App\Models\Service;
use App\Models\User;

interface ServiceCreationStrategy
{
    /**
     * Create a new service of a specific type
     *
     * @param array $data
     * @param User $user
     * @return Service
     */
    public function create(array $data, User $user): Service;

    /**
     * Validate data for service creation
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

    /**
     * Detect service type from request data
     *
     * @param array $data
     * @return string|null
     */
    public function detectServiceType(array $data): ?string;
}

