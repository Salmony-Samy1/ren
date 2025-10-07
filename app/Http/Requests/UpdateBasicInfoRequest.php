<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBasicInfoRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $userId = $this->user()->id;
        return [
            'email'         => 'sometimes|email|unique:users,email,' . $userId,
            'phone'         => 'sometimes|string|unique:users,phone,' . $userId,
            'country_id'  => 'sometimes|exists:countries,id',
            'full_name'     => 'sometimes|string|max:255',
            'national_id'   => 'sometimes|string|max:255',
            'avatar'        => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:10240',
        ];
    }
}