<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserTermsAgreement;
use App\Models\LegalPage;

class EnsureTermsAccepted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $requiredPageSlug = 'all-provider-terms')
    {
        // Try API guard first (token-based), then fallback to default user()
        $user = $request->user('api') ?? auth('api')->user() ?? $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً',
                'data' => []
            ], 401);
        }
        
        // إذا كان المستخدم مقدم خدمة، تحقق من جميع الصفحات القانونية المطلوبة
        if ($user->type === 'provider') {
            $requiredLegalPages = [
                'terms-of-service-provider',
                'pricing-seasonality-policy',
                'refund-cancellation-policy',
                'privacy-policy',
                'advertising-policy',
                'acceptable-content-policy',
                'contract-continuity-terms',
                'customer-response-policy',
            ];
            
            $missingAgreements = [];
            
            foreach ($requiredLegalPages as $slug) {
                $legalPage = LegalPage::where('slug', $slug)->first();
                
                if ($legalPage) {
                    $hasAgreed = UserTermsAgreement::where('user_id', $user->id)
                        ->where('legal_page_id', $legalPage->id)
                        ->where('status', 'accepted')
                        ->exists();
                    
                    if (!$hasAgreed) {
                        $missingAgreements[] = [
                            'id' => $legalPage->id,
                            'title' => $legalPage->title,
                            'slug' => $legalPage->slug,
                            'content' => $legalPage->content,
                        ];
                    }
                }
            }
            
            if (!empty($missingAgreements)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب الموافقة على جميع الشروط والأحكام أولاً',
                    'data' => [
                        'requires_terms_acceptance' => true,
                        'missing_agreements' => $missingAgreements,
                        'acceptance_url' => '/api/v1/app/legal/accept-terms'
                    ]
                ], 403);
            }
        } else {
            // للمستخدمين العاديين، تحقق من صفحة واحدة فقط
            // $legalPage = LegalPage::where('slug', $requiredPageSlug)->first();
            
            // if (!$legalPage) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'الصفحة القانونية المطلوبة غير موجودة',
            //         'data' => []
            //     ], 500);
            // }
            
            // $hasAgreed = UserTermsAgreement::where('user_id', $user->id)
            //     ->where('legal_page_id', $legalPage->id)
            //     ->where('status', 'accepted')
            //     ->exists();
            
            // if (!$hasAgreed) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'يجب الموافقة على الشروط والأحكام أولاً',
            //         'data' => [
            //             'requires_terms_acceptance' => true,
            //             'legal_page' => [
            //                 'id' => $legalPage->id,
            //                 'title' => $legalPage->title,
            //                 'slug' => $legalPage->slug,
            //                 'content' => $legalPage->content,
            //             ],
            //             'acceptance_url' => '/api/v1/app/legal/accept-terms'
            //         ]
            //     ], 403);
            // }
        }
        
        return $next($request);
    }
}
