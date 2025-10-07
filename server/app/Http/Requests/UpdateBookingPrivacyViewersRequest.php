<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingPrivacyViewersRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'viewer_user_ids' => 'required|array|min:1',
            'viewer_user_ids.*' => 'integer|exists:users,id',
        ];
    }
}

