<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use App\Models\UserTermsAgreement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegalController extends Controller
{
    /**
     * عرض صفحة قانونية محددة
     */
    public function show($slug)
    {
        $legalPage = LegalPage::where('slug', $slug)->first();
        
        if (!$legalPage) {
            return format_response(false, 'الصفحة القانونية غير موجودة', [], 404);
        }
        
        return format_response(true, 'تم جلب الصفحة القانونية بنجاح', [
            'id' => $legalPage->id,
            'title' => $legalPage->title,
            'slug' => $legalPage->slug,
            'content' => $legalPage->content,
            'created_at' => $legalPage->created_at,
            'updated_at' => $legalPage->updated_at,
        ]);
    }
    
    /**
     * جلب جميع الصفحات القانونية
     */
    public function index()
    {
        $legalPages = LegalPage::select('id', 'title','content', 'slug', 'created_at', 'updated_at')->get();
        
        return format_response(true, 'تم جلب الصفحات القانونية بنجاح', $legalPages);
    }
    
    /**
     * جلب شروط الاستخدام
     */
    public function terms()
    {
        $terms = LegalPage::where('slug', 'terms-of-service-provider')->first();
        
        if (!$terms) {
            return format_response(false, 'شروط الاستخدام غير موجودة', [], 404);
        }
        
        return format_response(true, 'تم جلب شروط الاستخدام بنجاح', [
            'id' => $terms->id,
            'title' => $terms->title,
            'content' => $terms->content,
            'updated_at' => $terms->updated_at,
        ]);
    }
    
    /**
     * جلب سياسة الخصوصية
     */
    public function privacy()
    {
        $privacy = LegalPage::where('slug', 'privacy-policy')->first();
        
        if (!$privacy) {
            return format_response(false, 'سياسة الخصوصية غير موجودة', [], 404);
        }
        
        return format_response(true, 'تم جلب سياسة الخصوصية بنجاح', [
            'id' => $privacy->id,
            'title' => $privacy->title,
            'content' => $privacy->content,
            'updated_at' => $privacy->updated_at,
        ]);
    }
    
    /**
     * موافقة المستخدم على الشروط والأحكام
     */
    public function acceptTerms(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return format_response(false, 'يجب تسجيل الدخول أولاً', [], 401);
        }
        
        $request->validate([
            'legal_page_id' => 'required|exists:legal_pages,id',
            'terms_accepted' => 'required|boolean|accepted',
        ], [
            'legal_page_id.required' => 'معرف الصفحة القانونية مطلوب',
            'legal_page_id.exists' => 'الصفحة القانونية غير موجودة',
            'terms_accepted.required' => 'يجب قبول الشروط والأحكام',
            'terms_accepted.accepted' => 'يجب قبول الشروط والأحكام',
        ]);
        
        // التحقق من عدم الموافقة مسبقاً
        $existingAgreement = UserTermsAgreement::where('user_id', $user->id)
            ->where('legal_page_id', $request->legal_page_id)
            ->first();
            
        if ($existingAgreement) {
            return format_response(false, 'لقد وافقت على هذه الشروط مسبقاً', [], 400);
        }
        
        // إنشاء سجل الموافقة
        $agreement = UserTermsAgreement::create([
            'user_id' => $user->id,
            'legal_page_id' => $request->legal_page_id,
            'agreed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        return format_response(true, 'تم قبول الشروط والأحكام بنجاح', [
            'agreement_id' => $agreement->id,
            'agreed_at' => $agreement->agreed_at,
            'legal_page_id' => $agreement->legal_page_id,
        ]);
    }
    
    /**
     * جلب موافقات المستخدم على الشروط والأحكام
     */
    public function userAgreements()
    {
        $user = Auth::user();
        
        if (!$user) {
            return format_response(false, 'يجب تسجيل الدخول أولاً', [], 401);
        }
        
        $agreements = UserTermsAgreement::with('legalPage')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($agreement) {
                return [
                    'id' => $agreement->id,
                    'legal_page' => [
                        'id' => $agreement->legalPage->id,
                        'title' => $agreement->legalPage->title,
                        'slug' => $agreement->legalPage->slug,
                    ],
                    'agreed_at' => $agreement->agreed_at,
                    'ip_address' => $agreement->ip_address,
                ];
            });
        
        return format_response(true, 'تم جلب موافقات المستخدم بنجاح', $agreements);
    }
    
    /**
     * التحقق من موافقة المستخدم على شروط محددة
     */
    public function checkAgreement($legalPageId)
    {
        $user = Auth::user();
        
        if (!$user) {
            return format_response(false, 'يجب تسجيل الدخول أولاً', [], 401);
        }
        
        $agreement = UserTermsAgreement::where('user_id', $user->id)
            ->where('legal_page_id', $legalPageId)
            ->first();
        
        $hasAgreed = $agreement ? true : false;
        
        return format_response(true, 'تم التحقق من الموافقة', [
            'has_agreed' => $hasAgreed,
            'agreed_at' => $hasAgreed ? $agreement->agreed_at : null,
            'legal_page_id' => $legalPageId,
        ]);
    }
}