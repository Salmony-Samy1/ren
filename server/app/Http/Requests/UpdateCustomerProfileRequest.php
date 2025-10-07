<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'gender'          => 'required|string|in:male,female',
            
            'region_id'       => 'required|exists:regions,id',
            'neigbourhood_id' => 'required|exists:neigbourhoods,id',
            'hobby_ids'       => 'sometimes|array',
            'hobby_ids.*'     => 'integer|exists:hobbies,id',
        ];
    }
}