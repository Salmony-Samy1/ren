<?php

namespace App\Services;

use App\Models\LegalPage;
use App\Models\UserTermsAgreement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CateringLegalTermsService
{
    /**
     * التحقق من الموافقة على الشروط القانونية للكيترينج
     */
    public function checkLegalCompliance(int $userId): array
    {
        $cateringTermsPages = LegalPage::whereIn('slug', [
            'catering-refund-policy' // Only one page required: refund policy
        ])->get();

        $missingAgreements = [];
        $compliant = true;

        foreach ($cateringTermsPages as $page) {
            $agreement = UserTermsAgreement::where('user_id', $userId)
                ->where('legal_page_id', $page->id)
                ->where('status', 'accepted')
                ->whereNotNull('accepted_at')
                ->first();

            if (!$agreement) {
                $missingAgreements[] = [
                    'legal_page_id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug
                ];
                $compliant = false;
            }
        }

        return [
            'is_compliant' => $compliant,
            'missing_agreements' => $missingAgreements,
            'required_pages_count' => $cateringTermsPages->count(),
            'agreed_pages_count' => $cateringTermsPages->count() - count($missingAgreements)
        ];
    }

    /**
     * تسجيل الموافقة على الشروط القانونية الجديدة
     */
    public function recordLegalAcceptance(int $userId, array $legalPageIds, array $data = []): array
    {
        $results = [];
        
        try {
            DB::beginTransaction();
            
            foreach ($legalPageIds as $pageId) {
                // التحقق من وجود الصفحة القانونية
                $legalPage = LegalPage::find($pageId);
                if (!$legalPage) {
                    continue;
                }

                // إنشاء أو تحديث الموافقة
                $agreement = UserTermsAgreement::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'legal_page_id' => $pageId,
                    ],
                    [
                        'status' => 'accepted',
                        'accepted_at' => now(),
                        'admin_notes' => $data['admin_notes'] ?? null,
                    ]
                );

                $results[] = [
                    'legal_page_id' => $pageId,
                    'title' => $legalPage->title,
                    'slug' => $legalPage->slug,
                    'agreement_id' => $agreement->id,
                    'accepted_at' => $agreement->accepted_at->toISOString()
                ];

                Log::info("Legal terms acceptance recorded", [
                    'user_id' => $userId,
                    'legal_page_id' => $pageId,
                    'agreement_id' => $agreement->id,
                    'page_title' => $legalPage->title
                ]);
            }

            DB::commit();
            
            return [
                'success' => true,
                'accepted_terms' => $results,
                'message' => 'تم تسجيل الموافقة على الشروط القانونية بنجاح'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to record legal acceptance", [
                'user_id' => $userId,
                'legal_page_ids' => $legalPageIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'فشل في تسجيل الموافقة على الشروط القانونية',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * جلب جميع الشروط القانونية المطلوبة للكيترينج
     */
    public function getRequiredCateringTerms(): array
    {
        $terms = LegalPage::whereIn('slug', [
            'catering-refund-policy' // Only one page required: refund policy
        ])->orderBy('id')->get();

        return $terms->map(function ($term) {
            return [
                'id' => $term->id,
                'title' => $term->title,
                'slug' => $term->slug,
                'required' => true,
                'content_preview' => substr(strip_tags($term->content), 0, 200) . '...'
            ];
        })->toArray();
    }

    /**
     * التحقق من استيفاء شروط الكيترينج قبل إنشاء الخدمة
     */
    public function validateBeforeCreatingCateringService(int $userId): array
    {
        $compliance = $this->checkLegalCompliance($userId);
        
        if (!$compliance['is_compliant']) {
            return [
                'can_proceed' => false,
                'message' => 'يجب الموافقة على جميع الشروط القانونية قبل إنشاء خدمة الكيترينج',
                'required_agreements' => $compliance['missing_agreements'],
                'compliance_details' => $compliance
            ];
        }

        return [
            'can_proceed' => true,
            'message' => 'جميع الشروط القانونية مستوفاة',
            'compliance_details' => $compliance
        ];
    }

    /**
     * جلب سجل الموافقات للمستخدم
     */
    public function getUserAgreements(int $userId): array
    {
        $agreements = UserTermsAgreement::with(['legalPage'])
            ->where('user_id', $userId)
            ->where('status', 'accepted')
            ->orderBy('accepted_at', 'desc')
            ->get();

        return $agreements->map(function ($agreement) {
            return [
                'id' => $agreement->id,
                'legal_page' => [
                    'id' => $agreement->legalPage->id,
                    'title' => $agreement->legalPage->title,
                    'slug' => $agreement->legalPage->slug,
                ],
                'status' => $agreement->status,
                'accepted_at' => $agreement->accepted_at?->toISOString(),
                'admin_notes' => $agreement->admin_notes
            ];
        })->toArray();
    }

    /**
     * جلب الصفحات القانونية المطلوبة كـ API endpoint
     */
    public function getLegalPagesForApi(): array
    {
        $pages = $this->getRequiredCateringTerms();
        
        return [
            'legal_pages' => $pages,
            'count' => count($pages),
            'message' => 'الشروط القانونية المطلوبة لخدمة الكيترينج',
            'last_updated' => now()->toISOString()
        ];
    }
}
