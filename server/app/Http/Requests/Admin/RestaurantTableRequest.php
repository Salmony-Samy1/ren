<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RestaurantTableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'sometimes|nullable|string|max:120',
            'type' => 'required|in:Normal,VIP',
            'capacity_people' => 'required|integer|min:1|max:50',
            'quantity' => 'required|integer|min:1|max:100',
            're_availability_type' => 'required|in:AUTO,MANUAL',
            'auto_re_availability_minutes' => 'nullable|integer|min:1|max:1440', // max 24 hours
            'conditions' => 'sometimes|array',
            'amenities' => 'sometimes|array',
        ];

        // Add conditional validation based on table type
        if ($this->input('type') === 'Normal') {
            $rules['price_per_person'] = 'required|numeric|min:0|max:999999.99';
            $rules['price_per_table'] = 'nullable|numeric|min:0|max:999999.99';
        } elseif ($this->input('type') === 'VIP') {
            $rules['price_per_table'] = 'required|numeric|min:0|max:999999.99';
            $rules['price_per_person'] = 'nullable|numeric|min:0|max:999999.99';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'اسم الطاولة لا يجب أن يتجاوز 120 حرف',
            'type.required' => 'نوع الطاولة مطلوب',
            'type.in' => 'نوع الطاولة يجب أن يكون Normal أو VIP',
            'capacity_people.required' => 'سعة الطاولة مطلوبة',
            'capacity_people.integer' => 'سعة الطاولة يجب أن تكون رقم صحيح',
            'capacity_people.min' => 'سعة الطاولة يجب أن تكون على الأقل 1 شخص',
            'capacity_people.max' => 'سعة الطاولة لا يجب أن تتجاوز 50 شخص',
            'quantity.required' => 'عدد الطاولات مطلوب',
            'quantity.integer' => 'عدد الطاولات يجب أن يكون رقم صحيح',
            'quantity.min' => 'عدد الطاولات يجب أن يكون على الأقل 1',
            'quantity.max' => 'عدد الطاولات لا يجب أن يتجاوز 100',
            're_availability_type.required' => 'نوع إعادة التوفر مطلوب',
            're_availability_type.in' => 'نوع إعادة التوفر يجب أن يكون AUTO أو MANUAL',
            'auto_re_availability_minutes.integer' => 'دقائق إعادة التوفر يجب أن تكون رقم صحيح',
            'auto_re_availability_minutes.min' => 'دقائق إعادة التوفر يجب أن تكون على الأقل 1 دقيقة',
            'auto_re_availability_minutes.max' => 'دقائق إعادة التوفر لا يجب أن تتجاوز 1440 دقيقة (24 ساعة)',
            'price_per_person.required' => 'سعر الفرد مطلوب للطاولات العادية',
            'price_per_person.numeric' => 'سعر الفرد يجب أن يكون رقم',
            'price_per_person.min' => 'سعر الفرد يجب أن يكون أكبر من أو يساوي 0',
            'price_per_person.max' => 'سعر الفرد لا يجب أن يتجاوز 999999.99',
            'price_per_table.required' => 'سعر الطاولة مطلوب للطاولات VIP',
            'price_per_table.numeric' => 'سعر الطاولة يجب أن يكون رقم',
            'price_per_table.min' => 'سعر الطاولة يجب أن يكون أكبر من أو يساوي 0',
            'price_per_table.max' => 'سعر الطاولة لا يجب أن يتجاوز 999999.99',
            'conditions.array' => 'شروط الحجز يجب أن تكون مصفوفة',
            'amenities.array' => 'المميزات يجب أن تكون مصفوفة',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الطاولة',
            'type' => 'نوع الطاولة',
            'capacity_people' => 'سعة الطاولة',
            'price_per_person' => 'سعر الفرد',
            'price_per_table' => 'سعر الطاولة',
            'quantity' => 'عدد الطاولات',
            're_availability_type' => 'نوع إعادة التوفر',
            'auto_re_availability_minutes' => 'دقائق إعادة التوفر',
            'conditions' => 'شروط الحجز',
            'amenities' => 'المميزات',
        ];
    }
}

