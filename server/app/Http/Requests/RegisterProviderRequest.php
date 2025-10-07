<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\MainServiceRequirementsService;

class RegisterProviderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // الحقول الأساسية الموجودة
            'company_name' => 'required|string|max:255',
            'owner' => 'required|string|max:255',
            'national_id' => [
                'required',
                'regex:/^(\d{9}|\d{10})$/',
                'unique:users,national_id'
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+?973\d{8}|\+?9665\d{8}|5\d{8}|\d{8})$/',
                'unique:users,phone'
            ],
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:cities,id',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
            
            // الحقول الجديدة المطلوبة
            'nationality_id' => 'required|exists:nationalities,id',
            'iban' => 'required|string|max:34',
            'tourism_license_number' => 'required|string|max:255',
            'kyc_id' => 'required|string|max:255',
            'main_service_id' => 'required|exists:main_services,id',
            'region_id' => 'required|exists:regions,id',
            'terms-of-service-provider' => 'required|boolean|accepted',
            'pricing-seasonality-policy' => 'required|boolean|accepted',
            'refund-cancellation-policy' => 'required|boolean|accepted',
            'privacy-policy' => 'required|boolean|accepted',
            'advertising-policy' => 'required|boolean|accepted',
            'acceptable-content-policy' => 'required|boolean|accepted',
            'contract-continuity-terms' => 'required|boolean|accepted',
            'customer-response-policy' => 'required|boolean|accepted',
            'description' => 'required|string|max:1000',
            
            // الهوايات/الاهتمامات (اختيارية)
            'hobbies' => 'nullable|array',
            'hobbies.*' => 'required|exists:hobbies,id',
            
            // المستندات القانونية - ستكون ديناميكية حسب الخدمة والدولة
            'legal_documents' => 'nullable|array',
            'legal_documents.*.type' => 'required_with:legal_documents|string',
            'legal_documents.*.file' => 'required_with:legal_documents.*.type|file|mimes:pdf,jpeg,png,jpg,jpeg|max:10240',
            'legal_documents.*.start_date' => 'required_with:legal_documents.*.type|date',
            'legal_documents.*.end_date' => 'required_with:legal_documents.*.type|date|after:legal_documents.*.start_date',
            
            // الصور (اختيارية في التسجيل)
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function($validator) {
            $data = $this->all();
            
            // التحقق من أن المستندات المرفوعة تطابق المتطلبات المحددة
            if (isset($data['main_service_id']) && isset($data['country_id'])) {
                try {
                    $requirementsService = app(MainServiceRequirementsService::class);
                    $requirements = $requirementsService->getRequiredDocuments(
                        (int)$data['main_service_id'],
                        (int)$data['country_id']
                    );
                    
                    $requiredDocTypes = $requirements->where('is_required', true)->pluck('document_type')->map(fn($type) => $type->value)->toArray();
                    $submittedDocTypes = isset($data['legal_documents']) ? collect($data['legal_documents'])->pluck('type')->toArray() : [];
                    
                    // التحقق من صحة أنواع المستندات المرفوعة
                    if (isset($data['legal_documents'])) {
                        foreach ($data['legal_documents'] as $index => $doc) {
                            if (!in_array($doc['type'], $requiredDocTypes)) {
                                $validator->errors()->add("legal_documents.{$index}.type", 'نوع المستند غير صحيح: ' . $doc['type']);
                            }
                        }
                    }
                    
                    // التحقق من وجود جميع المستندات المطلوبة
                    if (!empty($requiredDocTypes)) {
                        $missingRequiredDocs = array_diff($requiredDocTypes, $submittedDocTypes);
                        if (!empty($missingRequiredDocs)) {
                            $validator->errors()->add('legal_documents', 'المستندات المطلوبة المفقودة: ' . implode(', ', $missingRequiredDocs));
                        }
                        
                        // التحقق من عدم وجود مستندات غير مطلوبة
                        $extraDocs = array_diff($submittedDocTypes, $requiredDocTypes);
                        if (!empty($extraDocs)) {
                            $validator->errors()->add('legal_documents', 'مستندات غير مطلوبة: ' . implode(', ', $extraDocs));
                        }
                    }
                    
                } catch (\Exception $e) {
                    $validator->errors()->add('legal_documents', 'خطأ في التحقق من متطلبات المستندات: ' . $e->getMessage());
                }
            }
        });
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'اسم الشركة مطلوب',
            'owner.required' => 'اسم صاحب الشركة مطلوب',
            'national_id.required' => 'رقم الهوية الوطنية مطلوب',
            'national_id.unique' => 'رقم الهوية الوطنية مستخدم مسبقاً',
            'phone.required' => 'رقم الجوال مطلوب',
            'phone.unique' => 'رقم الجوال مستخدم مسبقاً',
            'country_code.required' => 'رمز الدولة مطلوب',
            'city_id.required' => 'المدينة مطلوبة',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'nationality_id.required' => 'الجنسية مطلوبة',
            'nationality_id.exists' => 'الجنسية المختارة غير صحيحة',
            'iban.required' => 'رقم الآيبان مطلوب',
            'tourism_license_number.required' => 'رقم ترخيص وزارة السياحة مطلوب',
            'kyc_id.required' => 'رقم KYC مطلوب',
            'main_service_id.required' => 'نوع الخدمة مطلوب',
            'region_id.required' => 'المنطقة مطلوبة',
            'terms-of-service-provider.required' => 'يجب قبول اتفاقية الاستخدام',
            'terms-of-service-provider.accepted' => 'يجب قبول اتفاقية الاستخدام',
            'pricing-seasonality-policy.required' => 'يجب قبول سياسة الأسعار والموسمية',
            'pricing-seasonality-policy.accepted' => 'يجب قبول سياسة الأسعار والموسمية',
            'refund-cancellation-policy.required' => 'يجب قبول سياسة الاسترجاع والإلغاء',
            'refund-cancellation-policy.accepted' => 'يجب قبول سياسة الاسترجاع والإلغاء',
            'privacy-policy.required' => 'يجب قبول سياسة الخصوصية',
            'privacy-policy.accepted' => 'يجب قبول سياسة الخصوصية',
            'advertising-policy.required' => 'يجب قبول سياسة الإعلانات',
            'advertising-policy.accepted' => 'يجب قبول سياسة الإعلانات',
            'acceptable-content-policy.required' => 'يجب قبول سياسة المحتوى المقبول',
            'acceptable-content-policy.accepted' => 'يجب قبول سياسة المحتوى المقبول',
            'contract-continuity-terms.required' => 'يجب قبول شروط التعاقد والاستمرار',
            'contract-continuity-terms.accepted' => 'يجب قبول شروط التعاقد والاستمرار',
            'customer-response-policy.required' => 'يجب قبول سياسة الرد على العملاء',
            'customer-response-policy.accepted' => 'يجب قبول سياسة الرد على العملاء',
            'description.required' => 'البذة التعريفية عن الشركة مطلوبة',
            'legal_documents.required' => 'المستندات القانونية مطلوبة',
            'legal_documents.*.type.required' => 'نوع المستند مطلوب',
            'legal_documents.*.file.required' => 'ملف المستند مطلوب',
            'legal_documents.*.start_date.required' => 'تاريخ بداية المستند مطلوب',
            'legal_documents.*.end_date.required' => 'تاريخ انتهاء المستند مطلوب',
        ];
    }
}