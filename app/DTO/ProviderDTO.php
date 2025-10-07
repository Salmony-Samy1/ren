<?php

namespace App\DTO;

use App\Repositories\CityRepo\ICityRepo;

class ProviderDTO
{
    private string $type = 'provider';

    public function __construct(
        public string              $name,
        public string              $owner,
        public string              $national_id,
        public string              $email,
        public string              $password,
        public string              $phone,
        public string              $city_id,
        public int        $country_id,
        private readonly ICityRepo $cityRepo,
        public ?string             $nationality_id = null,
        public ?string             $iban = null,
        public ?string             $tourism_license_number = null,
        public ?string             $kyc_id = null,
        public ?string             $main_service_id = null,
        public ?string             $region_id = null,
        public ?string             $service_classification = null,
        public ?string             $description = null,
        public ?array              $hobbies = null,
        public ?array              $legal_documents = null,
        public ?string             $company_logo = null,
        public ?string             $avatar = null,
        public ?bool               $terms_of_service_provider = null,
        public ?bool               $pricing_seasonality_policy = null,
        public ?bool               $refund_cancellation_policy = null,
        public ?bool               $privacy_policy = null,
        public ?bool               $advertising_policy = null,
        public ?bool               $acceptable_content_policy = null,
        public ?bool               $contract_continuity_terms = null,
        public ?bool               $customer_response_policy = null
    )
    {
        if ($this->country_id == null) {
            $city = $this->cityRepo->getById($this->city_id);
            if ($city) {
                $this->country_id = $city->country_id;
            }
        }
    }


    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'owner' => $this->owner,
            'national_id' => $this->national_id,
            'email' => $this->email,
            'password' => $this->password,
            'phone' => $this->phone,
            'city_id' => $this->city_id,
            'country_id' => $this->country_id,
            'type' => $this->type,
            'nationality_id' => $this->nationality_id,
            'iban' => $this->iban,
            'tourism_license_number' => $this->tourism_license_number,
            'kyc_id' => $this->kyc_id,
            'main_service_id' => $this->main_service_id,
            'region_id' => $this->region_id,
            'service_classification' => $this->service_classification,
            'description' => $this->description,
            'hobbies' => $this->hobbies,
            'legal_documents' => $this->legal_documents,
            'company_logo' => $this->company_logo,
            'avatar' => $this->avatar,
            'terms_of_service_provider' => $this->terms_of_service_provider,
            'pricing_seasonality_policy' => $this->pricing_seasonality_policy,
            'refund_cancellation_policy' => $this->refund_cancellation_policy,
            'privacy_policy' => $this->privacy_policy,
            'advertising_policy' => $this->advertising_policy,
            'acceptable_content_policy' => $this->acceptable_content_policy,
            'contract_continuity_terms' => $this->contract_continuity_terms,
            'customer_response_policy' => $this->customer_response_policy
        ];
    }

}
