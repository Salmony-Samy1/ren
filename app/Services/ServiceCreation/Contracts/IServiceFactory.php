<?php

namespace App\Services\ServiceCreation\Contracts;

use App\Models\Service;

interface IServiceFactory
{
    /**
     * إنشاء خدمة جديدة بناءً على نوع الخدمة المحدد
     *
     * @param array $data
     * @return Service
     * @throws \InvalidArgumentException
     */
    public function createService(array $data): Service;
    
    /**
     * تحديد نوع الخدمة من البيانات المرسلة
     *
     * @param array $data
     * @return string
     */
    public function detectServiceType(array $data): string;
}
