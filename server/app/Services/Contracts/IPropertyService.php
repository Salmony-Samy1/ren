<?php

namespace App\Services\Contracts;

use App\Models\Service;

/**
 * Interface IPropertyService
 * This contract defines the methods for managing property-related services.
 * The method signatures have been updated to match the concrete implementation, resolving the compatibility error.
 *
 * هذا العقد يحدد الدوال اللازمة لإدارة الخدمات المتعلقة بالعقارات.
 * تم تحديث تعريف الدوال لتطابق التنفيذ الفعلي، مما يحل خطأ عدم التوافق.
 */
interface IPropertyService
{
    /**
     * Creates a new Service and its associated Property.
     *
     * @param array $payload The entire validated data from the request.
     * @return Service The newly created parent service instance.
     */
    public function createProperty(array $payload): Service;

    /**
     * Updates an existing Service and its associated Property.
     *
     * @param Service $service The parent service instance to update.
     * @param array $payload The validated data from the request.
     * @return Service The updated service instance.
     */
    public function updateProperty(Service $service, array $payload): Service;

    /**
     * Deletes a Service and its associated Property.
     *
     * @param Service $service The parent service instance to delete.
     * @return void
     */
    public function deleteProperty(Service $service): void;
}

