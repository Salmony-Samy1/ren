<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\CateringLegalTermsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CateringLegalTermsController extends Controller
{
    protected CateringLegalTermsService $legalTermsService;

    public function __construct(CateringLegalTermsService $legalTermsService)
    {
        $this->legalTermsService = $legalTermsService;
    }

    /**
     * جلب الشروط القانونية المطلوبة للكيترينج
     */
    public function getRequiredTerms(): JsonResponse
    {
        $terms = $this->legalTermsService->getLegalPagesForApi();
        
        return response()->json([
            'success' => true,
            'data' => $terms,
            'message' => 'تم جلب الشروط القانونية المطلوبة بنجاح'
        ]);
    }

    /**
     * التحقق من استيفاء الشروط القانونية
     */
    public function checkCompliance(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $compliance = $this->legalTermsService->checkLegalCompliance($userId);
        
        return response()->json([
            'success' => true,
            'data' => $compliance,
            'message' => $compliance['is_compliant'] 
                ? 'جميع الشروط القانونية مستوفاة' 
                : 'يجب الموافقة على الشروط القانونية المفقودة'
        ]);
    }

    /**
     * تسجيل الموافقة على الشروط القانونية
     */
    public function acceptTerms(Request $request): JsonResponse
    {
        $request->validate([
            'legal_page_ids' => 'required|array',
            'legal_page_ids.*' => 'exists:legal_pages,id',
            'admin_notes' => 'nullable|string'
        ]);

        $userId = $request->user()->id;
        $legalPageIds = $request->input('legal_page_ids');
        $adminNotes = $request->input('admin_notes');

        $result = $this->legalTermsService->recordLegalAcceptance(
            $userId, 
            $legalPageIds,
            ['admin_notes' => $adminNotes]
        );

        return response()->json([
            'success' => $result['success'],
            'data' => $result,
            'message' => $result['success'] 
                ? 'تم تسجيل الموافقة بنجاح' 
                : 'فشل في تسجيل الموافقة'
        ], $result['success'] ? 200 : 400);
    }

    /**
     * جلب موافقات المستخدم الحالي
     */
    public function getUserAgreements(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $agreements = $this->legalTermsService->getUserAgreements($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'agreements' => $agreements,
                'count' => count($agreements)
            ],
            'message' => 'تم جلب موافقات المستخدم بنجاح'
        ]);
    }

    /**
     * فحص أهلية إنشاء خدمة كيترينج
     */
    public function checkEligibility(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $validation = $this->legalTermsService->validateBeforeCreatingCateringService($userId);

        return response()->json([
            'success' => $validation['can_proceed'],
            'data' => $validation,
            'message' => $validation['message']
        ], $validation['can_proceed'] ? 200 : 422);
    }
}
