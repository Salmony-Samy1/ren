<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => 'required|exists:services,id',
            // dates optional for events; if missing, default from event window
            'start_date' => 'sometimes|date|after_or_equal:now',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            // allow optional capacity inputs for precise availability checks
            'booking_details' => 'sometimes|array',
            'booking_details.number_of_people' => 'sometimes|integer|min:1',
            'booking_details.number_of_items' => 'sometimes|integer|min:1',
        ];
    }
}

