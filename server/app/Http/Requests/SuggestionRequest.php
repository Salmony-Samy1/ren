<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuggestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'nullable|in:low,medium,high',
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
            'title.required' => 'عنوان الاقتراح مطلوب',
            'title.max' => 'عنوان الاقتراح يجب ألا يتجاوز 255 حرف',
            'description.required' => 'وصف الاقتراح مطلوب',
            'description.max' => 'وصف الاقتراح يجب ألا يتجاوز 2000 حرف',
            'priority.in' => 'الأولوية يجب أن تكون من القيم المسموحة',
            'images.array' => 'الصور يجب أن تكون مصفوفة',
            'images.max' => 'يمكن رفع 5 صور كحد أقصى',
            'images.*.file' => 'كل عنصر يجب أن يكون ملف',
            'images.*.mimes' => 'نوع الملف يجب أن يكون jpg, jpeg, png, أو webp',
            'images.*.max' => 'حجم كل صورة يجب ألا يتجاوز 2 ميجابايت',
        ];
    }
}