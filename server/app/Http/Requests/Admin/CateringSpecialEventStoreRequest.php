<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CateringSpecialEventStoreRequest extends FormRequest
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
            'event_name' => ['required', 'string', 'max:200'],
            'event_type' => [
                'required',
                Rule::in(['wedding', 'conference', 'gala', 'corporate', 'charity', 'private_celebration'])
            ],
            'client_name' => ['required', 'string', 'max:100'],
            'client_phone' => ['required', 'string', 'regex:/^(\+966|966|0)?[567][0-9]{8}$/'],
            'client_email' => ['nullable', 'email', 'max:100'],
            'event_datetime' => ['required', 'date', 'after:now'],
            'venue_name' => ['required', 'string', 'max:150'],
            'full_address' => ['required', 'string', 'max:500'],
            'event_city' => ['required', 'string', 'max:50'],
            'event_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'event_long' => ['nullable', 'numeric', 'between:-180,180'],
            'guest_count' => ['required', 'integer', 'min:10', 'max:10000'],
            'estimated_budget' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'special_requirements' => ['nullable', 'array'],
            'special_requirements.*' => ['string', 'max:200'],
            'menu_items' => ['nullable', 'array'],
            'menu_items.*.name' => ['required_with:menu_items', 'string', 'max:100'],
            'menu_items.*.quantity' => ['required_with:menu_items', 'integer', 'min:1'],
            'menu_items.*.unit' => ['required_with:menu_items', 'string', 'max:20'],
            'menu_items.*.special_notes' => ['nullable', 'string', 'max:200'],
            'timeline' => ['nullable', 'array'],
            'timeline.*.milestone' => ['required_with:timeline', 'string', 'max:100'],
            'timeline.*.due_date' => ['required_with:timeline', 'date'],
            'timeline.*.completed' => ['nullable', 'boolean'],
            'contact_persons' => ['required', 'array', 'min:1'],
            'contact_persons.*.name' => ['required', 'string', 'max:100'],
            'contact_persons.*.role' => ['required', 'string', 'max:50'],
            'contact_persons.*.phone' => ['required', 'string', 'regex:/^(\+966|966|0)?[567][0-9]{8}$/'],
            'contact_persons.*.is_primary' => ['nullable', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'status' => [
                'nullable',
                Rule::in(['inquiry', 'planning', 'confirmed', 'in_progress', 'completed', 'cancelled'])
            ],
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
            'provider_id.exists' => 'مقدم الخدمة المحدد غير موجود',
            'event_name.required' => 'اسم المناسبة مطلوب',
            'event_name.max' => 'اسم المناسبة يجب أن يكون أقل من 200 حرف',
            'event_type.required' => 'نوع المناسبة مطلوب',
            'event_type.in' => 'نوع المناسبة غير صحيح',
            'client_name.required' => 'اسم العميل مطلوب',
            'client_phone.required' => 'رقم هاتف العميل مطلوب',
            'client_phone.regex' => 'رقم الهاته غير صحيح، يجب أن يبدأ بـ 5، 6، أو 7',
            'client_email.email' => 'البريد الإلكتروني غير صحيح',
            'event_datetime.required' => 'تاريخ ووقت المناسبة مطلوب',
            'event_datetime.after' => 'تاريخ ووقت المناسبة يجب أن يكون في المستقبل',
            'venue_name.required' => 'اسم المكان مطلوب',
            'full_address.required' => 'العنوان الكامل مطلوب',
            'event_city.required' => 'المدينة مطلوبة',
            'guest_count.required' => 'عدد الضيوف مطلوب',
            'guest_count.min' => 'عدد الضيوف يجب أن يكون 10 على الأقل',
            'guest_count.max' => 'عدد الضيوف يجب أن يكون 10000 على الأكثر',
            'estimated_budget.min' => 'الميزانية المقدرة يجب أن تكون أكبر من الصفر',
            'contact_persons.required' => 'ضروري وجود شخص اتصال واحد على الأقل',
            'contact_persons.min' => 'يجب تحديد شخص اتصال واحد على الأقل',
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

        // Ensure at least one primary contact
        if ($this->has('contact_persons') && is_array($this->contact_persons)) {
            $contactPersons = $this->contact_persons;
            $hasPrimary = false;
            
            foreach ($contactPersons as $contact) {
                if (isset($contact['is_primary']) && $contact['is_primary']) {
                    $hasPrimary = true;
                    break;
                }
            }

            // If no primary contact, make the first one primary
            if (!$hasPrimary && count($contactPersons) > 0) {
                $contactPersons[0]['is_primary'] = true;
                $this->merge(['contact_persons' => $contactPersons]);
            }
        }

        // Set default status if not provided
        if (!$this->has('status') || !$this->status) {
            $this->merge(['status' => 'inquiry']);
        }
    }
}