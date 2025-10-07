<?php

namespace App\Http\Requests\Onboarding;

use App\Enums\CompanyLegalDocType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MainServiceApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->type === 'provider';
    }

    public function rules(): array
    {
        return [
            'main_service_id' => ['required', 'exists:main_services,id'],
            'country_id' => ['required', 'exists:countries,id'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // Max 5MB
        ];
    }

    public function messages(): array
    {
        return [
            'documents.required' => 'المستندات مطلوبة. يرجى إرسال المستندات المطلوبة للخدمة والدولة المحددة.',
            'documents.array' => 'يجب إرسال المستندات كقائمة.',
            'documents.min' => 'يجب إرسال مستند واحد على الأقل.',
            'documents.*.file' => 'يجب أن يكون كل مستند ملفًا صالحًا.',
            'documents.*.mimes' => 'يجب أن تكون صيغة الملف pdf, jpg, jpeg, png.',
            'documents.*.max' => 'يجب ألا يتجاوز حجم الملف 5 ميجابايت.',
            'main_service_id.required' => 'معرف الخدمة الرئيسية مطلوب.',
            'main_service_id.exists' => 'الخدمة الرئيسية المحددة غير موجودة.',
            'country_id.required' => 'معرف الدولة مطلوب.',
            'country_id.exists' => 'الدولة المحددة غير موجودة.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Get all uploaded files and all text input data
            $documentFiles = $this->file('documents', []);
            $allRequestData = $this->all();

            // Debug: Log what we received
            \Log::info('Validation debug:', [
                'documentFiles' => $documentFiles,
                'allRequestData' => $allRequestData,
                '_POST' => $_POST,
                'request_all' => $this->all(),
                'request_input' => $this->input(),
                'request_files' => $this->allFiles()
            ]);

            if (empty($documentFiles)) {
                $validator->errors()->add('documents', 'لم يتم إرسال أي ملفات مستندات.');
                return;
            }

            // Iterate over the uploaded files and use their index to find related metadata
            // from the raw request data array.
            foreach ($documentFiles as $index => $file) {
                // Get metadata from $_POST (where Laravel puts the nested data)
                $docType = $_POST['documents'][$index]['type'] ?? null;
                $expiresAt = $_POST['documents'][$index]['expires_at'] ?? null;
                $status = $_POST['documents'][$index]['status'] ?? 'pending';

                // Define a user-friendly key for displaying errors, e.g., "documents.0.type"
                $errorKeyForType = "documents.{$index}.type";
                $errorKeyForFile = "documents.{$index}";
                $errorKeyForExpiry = "documents.{$index}.expires_at";


                if (is_null($file)) {
                    $validator->errors()->add($errorKeyForFile, 'الملف مطلوب لكل مستند.');
                    continue; // Skip to the next file in the loop
                }

                if (!$docType) {
                    $validator->errors()->add($errorKeyForType, "نوع المستند مطلوب للملف رقم {$index}.");
                } else {
                    // Validate that the provided docType is a valid enum case
                    try {
                        CompanyLegalDocType::from($docType);
                    } catch (\ValueError $e) {
                        $validator->errors()->add($errorKeyForType, "نوع المستند غير صحيح: {$docType}");
                    }
                }

                // Validate 'expires_at' if it exists for this document
                if ($expiresAt && !strtotime($expiresAt)) {
                    $validator->errors()->add($errorKeyForExpiry, 'تنسيق التاريخ غير صحيح لتاريخ الانتهاء.');
                }
            }
        });
    }
}

