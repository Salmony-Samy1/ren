<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TechnicalIssueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'category' => 'nullable|string|max:100',
            'images' => 'nullable|array|max:5',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'عنوان المشكلة مطلوب',
            'subject.max' => 'عنوان المشكلة يجب ألا يتجاوز 255 حرف',
            'message.required' => 'وصف المشكلة مطلوب',
            'message.max' => 'وصف المشكلة يجب ألا يتجاوز 2000 حرف',
            'priority.in' => 'الأولوية يجب أن تكون من القيم المسموحة',
            'category.max' => 'فئة المشكلة يجب ألا تتجاوز 100 حرف',
            'images.array' => 'الصور يجب أن تكون مصفوفة',
            'images.max' => 'يمكن رفع 5 صور كحد أقصى',
            'images.*.file' => 'كل عنصر يجب أن يكون ملف',
            'images.*.mimes' => 'نوع الملف يجب أن يكون jpg, jpeg, png, أو webp',
            'images.*.max' => 'حجم كل صورة يجب ألا يتجاوز 2 ميجابايت',
        ];
    }
}