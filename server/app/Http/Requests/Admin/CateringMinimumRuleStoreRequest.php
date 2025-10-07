<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CateringMinimumRuleStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->type === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider_id' => ['nullable', 'string', 'exists:users,id'],
            'rule_name' => ['required', 'string', 'max:150'],
            'region_name' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:50'],
            'center_lat' => ['required', 'numeric', 'between:-90,90'],
            'center_long' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'min:0', 'max:1000'],
            'min_order_value' => ['required', 'numeric', 'min:0', 'max:999999'],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'max:999999'],
            'free_delivery_threshold' => ['required', 'numeric', 'min:0', 'max:999999'],
            'max_delivery_distance_km' => ['required', 'numeric', 'min:0', 'max:1000'],
            'operating_hours' => ['required', 'array'],
            'operating_hours.sunday' => ['required', 'array'],
            'operating_hours.monday' => ['required', 'array'],
            'operating_hours.tuesday' => ['required', 'array'],
            'operating_hours.wednesday' => ['required', 'array'],
            'operating_hours.thursday' => ['required', 'array'],
            'operating_hours.friday' => ['required', 'array'],
            'operating_hours.saturday' => ['required', 'array'],
            'operating_hours.*.start' => ['required', 'date_format:H:i'],
            'operating_hours.*.end' => ['required', 'date_format:H:i'],
            'operating_hours.*.is_active' => ['required', 'boolean'],
            'special_conditions' => ['nullable', 'array'],
            'special_conditions.*' => ['string', 'max:300'],
            'is_active' => ['nullable', 'boolean'],
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive', 'suspended'])
            ],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages for Arabic responses.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider_id.exists' => 'مقدم الخدمة المحدد غير موجود',
            'rule_name.required' => 'اسم القاعدة مطلوب',
            'rule_name.max' => 'اسم القاعدة يجب أن يكون أقل من 150 حرف',
            'region_name.required' => 'اسم المنطقة مطلوب',
            'city.required' => 'اسم المدينة مطلوب',
            'center_lat.required' => 'خط العرض للمركز مطلوب',
            'center_lat.between' => 'خط العرض يجب أن يكون بين -90 و 90',
            'center_long.required' => 'خط الطول للمركز مطلوب',
            'center_long.between' => 'خط الطول يجب أن يكون بين -180 و 180',
            'radius_km.required' => 'نصف قطر التغطية مطلوب',
            'radius_km.min' => 'نصف قطر التغطية يجب أن يكون أكبر من الصفر',
            'radius_km.max' => 'نصف قطر التغطية يجب أن يكون أقل من 1000 كم',
            'min_order_value.required' => 'الحد الأدنى لقيمة الطلب مطلوب',
            'min_order_value.min' => 'الحد الأدنى لقيمة الطلب يجب أن يكون أكبر من الصفر',
            'delivery_fee.required' => 'رسوم التوصيل مطلوبة',
            'delivery_fee.min' => 'رسوم التوصيل يجب أن تكون أكبر أو تساوي الصفر',
            'free_delivery_threshold.required' => 'عتبة التوصيل المجاني مطلوبة',
            'free_delivery_threshold.min' => 'عتبة التوصيل المجاني يجب أن تكون أكبر من الصفر',
            'max_delivery_distance_km.required' => 'أقصى مسافة توصيل مطلوبة',
            'max_delivery_distance_km.min' => 'أقصى مسافة توصيل يجب أن تكون أكبر من الصفر',
            'operating_hours.required' => 'ساعات التشغيل مطلوبة',
            'operating_hours.*.start.required' => 'وقت البدء مطلوب لكل يوم',
            'operating_hours.*.end.required' => 'وقت الانتهاء مطلوب لكل يوم',
            'operating_hours.*.start.date_format' => 'وقت البدء يجب أن يكون بصيغة ساعة:دقيقة',
            'operating_hours.*.end.date_format' => 'وقت الانتهاء يجب أن يكون بصيغة ساعة:دقيقة',
            'operating_hours.*.is_active.required' => 'حالة النشاط مطلوبة لكل يوم',
            'special_conditions.*.max' => 'الشروط الخاصة يجب أن تكون أقل من 300 حرف',
            'admin_notes.max' => 'ملاحظات المشرف يجب أن تكون أقل من 1000 حرف',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert provider_id to null if empty
        if ($this->provider_id === '') {
            $this->merge(['provider_id' => null]);
        }

        // Ensure free delivery threshold is higher than minimum order value
        if ($this->has('min_order_value') && $this->has('free_delivery_threshold')) {
            if ($this->free_delivery_threshold <= $this->min_order_value) {
                $this->merge([
                    'free_delivery_threshold' => $this->min_order_value + 500
                ]);
            }
        }

        // Set default values
        if (!$this->has('is_active') || $this->is_active === null) {
            $this->merge(['is_active' => true]);
        }

        if (!$this->has('status') || !$this->status) {
            $this->merge(['status' => 'active']);
        }

        // Default operating hours if completely empty
        if (!$this->has('operating_hours') || empty($this->operating_hours)) {
            $this->merge([
                'operating_hours' => [
                    'sunday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
                    'monday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
                    'tuesday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
                    'wednesday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
                    'thursday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
                    'friday' => ['start' => '08:00', 'end' => '22:00', 'is_active' => true],
                    'saturday' => ['start' => '08:00', 'end' => '23:59', 'is_active' => true],
                ]
            ]);
        }
    }
}