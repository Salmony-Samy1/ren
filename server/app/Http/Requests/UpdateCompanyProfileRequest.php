<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'              => 'sometimes|string|max:255',
            'company_name'      => 'sometimes|string|max:255',
            'commercial_record' => 'sometimes|nullable|string|max:255',
            'tax_number'        => 'sometimes|nullable|string|max:255',
            'description'       => 'sometimes|nullable|string',
            'main_service_id'   => 'sometimes|nullable|exists:main_services,id',
            'owner'             => 'sometimes|string|max:255',
            'country_id'        => 'sometimes|exists:countries,id',
            'city_id'           => 'sometimes|exists:cities,id',
            'company_logo'      => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:10240',
        ];
    }
}