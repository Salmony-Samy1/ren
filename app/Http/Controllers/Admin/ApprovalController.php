<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\ServiceResource;
use App\Models\User;
use App\Models\Service;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(private readonly ApprovalService $approvalService)
    {
    }

    /**
     * عرض مقدمي الخدمات المعلقين
     */
    public function pendingProviders()
    {
        $providers = $this->approvalService->getPendingProviders();
        return response()->json([
            'success' => true,
            'data' => UserResource::collection($providers),
            'pagination' => [
                'current_page' => $providers->currentPage(),
                'last_page' => $providers->lastPage(),
                'per_page' => $providers->perPage(),
                'total' => $providers->total(),
            ]
        ]);
    }

    /**
     * عرض الخدمات المعلقة
     */
    public function pendingServices()
    {
        $services = $this->approvalService->getPendingServices();
        return response()->json([
            'success' => true,
            'data' => ServiceResource::collection($services),
            'pagination' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ]
        ]);
    }

    /**
     * موافقة على مقدم خدمة
     */
    public function approveProvider(Request $request, User $provider)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $result = $this->approvalService->approveProvider($provider, $request->notes);
        
        if ($result) {
            return format_response(true, __('Provider approved successfully'), new UserResource($provider));
        }
        
        return format_response(false, __('Failed to approve provider'), code: 500);
    }

    /**
     * رفض مقدم خدمة
     */
    public function rejectProvider(Request $request, User $provider)
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $result = $this->approvalService->rejectProvider($provider, $request->notes);
        
        if ($result) {
            return format_response(true, __('Provider rejected successfully'), new UserResource($provider));
        }
        
        return format_response(false, __('Failed to reject provider'), code: 500);
    }

    /**
     * موافقة على خدمة
     */
    public function approveService(Request $request, Service $service)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $result = $this->approvalService->approveService($service, $request->notes);
        
        if ($result) {
            return format_response(true, __('Service approved successfully'), new ServiceResource($service));
        }
        
        return format_response(false, __('Failed to approve service'), code: 500);
    }

    /**
     * رفض خدمة
     */
    public function rejectService(Request $request, Service $service)
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $result = $this->approvalService->rejectService($service, $request->notes);
        
        if ($result) {
            return format_response(true, __('Service rejected successfully'), new ServiceResource($service));
        }
        
        return format_response(false, __('Failed to reject service'), code: 500);
    }

    /**
     * تحديث إعدادات الموافقة
     */
    public function updateApprovalSettings(Request $request)
    {
        $request->validate([
            'auto_approve_providers' => 'boolean',
            'auto_approve_services' => 'boolean',
        ]);

        $result = $this->approvalService->updateApprovalSettings($request->all());
        
        if ($result) {
            return format_response(true, __('Approval settings updated successfully'));
        }
        
        return format_response(false, __('Failed to update approval settings'), code: 500);
    }

    /**
     * الحصول على إعدادات الموافقة
     */
    public function getApprovalSettings()
    {
        $settings = [
            'auto_approve_providers' => get_setting('auto_approve_providers', false),
            'auto_approve_services' => get_setting('auto_approve_services', false),
        ];

        return format_response(true, __('Settings fetched successfully'), $settings);
    }
}
