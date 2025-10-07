<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CateringDeliveryUpdateRequest extends FormRequest
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
            'status' => [
                'sometimes',
                'required',
                Rule::in(['scheduled', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'])
            ],
            'delivery_notes' => ['sometimes', 'string', 'nullable', 'max:500'],
            'admin_notes' => ['sometimes', 'string', 'nullable', 'max:1000'],
            'driver_id' => ['sometimes', 'string', 'nullable', 'max:50'],
            'driver_name' => ['sometimes', 'string', 'nullable', 'max:100'],
            'driver_phone' => ['sometimes', 'string', 'nullable', 'max:20'],
            'vehicle_plate' => ['sometimes', 'string', 'nullable', 'max:20'],
            'estimated_arrival' => ['sometimes', 'nullable', 'date'],
            'actual_delivery_time' => ['sometimes', 'nullable', 'date'],
            'delivery_person' => ['sometimes', 'string', 'nullable', 'max:100'],
        ];
    }

    /**
     * Get custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'حالة التوصيل يجب أن تكون واحدة من: مجدول، قيد التحضير، في الطريق للتوصيل، تم التوصيل، ملغي',
            'delivery_notes.max' => 'ملاحظات التوصيل يجب أن تكون أقل من 500 حرف',
            'admin_notes.max' => 'ملاحظات المشرف يجب أن تكون أقل من 1000 حرف',
            'driver_name.max' => 'اسم السائق يجب أن يكون أقل من 100 حرف',
            'driver_phone.max' => 'رقم هاتف السائق يجب أن يكون أقل من 20 حرف',
            'vehicle_plate.max' => 'رقم لوحة السيارة يجب أن يكون أقل من 20 حرف',
            'delivery_person.max' => 'اسم الشخص المستقبل يجب أن يكون أقل من 100 حرف',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure consistent status formatting
        if ($this->has('status')) {
            $this->merge([
                'status' => strtolower($this->status)
            ]);
        } 

        // Clean phone numbers
        if ($this->has('driver_phone')) {
            $this->merge([
                'driver_phone' => preg_replace('/[^+\d]/', '', $this->driver_phone)
            ]);
        }
    }
}