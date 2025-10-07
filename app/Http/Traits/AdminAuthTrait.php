<?php

namespace App\Http\Traits;

trait AdminAuthTrait
{
    /**
     * التحقق الموحد من صلاحيات المدير
     */
    protected function validateAdminAccess(): bool
    {
        return auth()->check() && 
               auth()->user()->type === 'admin' && 
               auth()->user()->status === 'active';
    }

    /**
     * إرجاع خطأ عدم التصريح
     */
    protected function forbiddenResponse(string $action = 'هذه العملية'): \Illuminate\Http\JsonResponse
    {
        return format_response(false, "غير مسموح لك بـ $action", [], 403);
    }

    /**
     * تسجيل خطأ في العمليات الإدارية
     */
    protected function logAdminError(string $operation, array $context = []): void
    {
        \Log::error("Admin $operation failed", array_merge([
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()->email ?? 'unknown'
        ], $context));
    }

    /**
     * معالجة الاستثناءات الموحدة للعمليات الإدارية
     */
    protected function handleAdminException(\Exception $e, string $operation, array $context = []): \Illuminate\Http\JsonResponse
    {
        $this->logAdminError($operation, array_merge([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], $context));

        return format_response(false, "فشل في $operation", [
            'error' => config('app.debug') ? $e->getMessage() : 'خطأ داخلي في النظام'
        ], 500);
    }
}

