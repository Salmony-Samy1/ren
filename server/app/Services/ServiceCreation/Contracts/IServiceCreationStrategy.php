<?php

namespace App\Services\ServiceCreation\Contracts;

use App\Models\Service;

interface IServiceCreationStrategy
{
    /**
     * إنشاء خدمة جديدة باستخدام الاستراتيجية المحددة
     *
     * @param array $data
     * @return Service
     */
    public function createService(array $data): Service;
    
    /**
     * التحقق من صحة البيانات المطلوبة للخدمة
     *
     * @param array $data
     * @return array
     */
    public function validateData(array $data): array;
    
    /**
     * الحصول على اسم Form Request المطلوب للتحقق
     *
     * @return string
     */
    public function getFormRequest(): string;
    
    /**
     * الحصول على نوع الخدمة
     *
     * @return string
     */
    public function getServiceType(): string;
}
