<?php

namespace App\DTO;

class CustomerDTO
{
    private string $type = 'customer';

    public function __construct(
        public string     $first_name,
        public string     $last_name,
        public string     $gender,
        public string     $email,
        public string     $password,
        public string     $phone,
        public int        $country_id,
        public int        $region_id,
        public int        $neigbourhood_id,
        public string     $national_id,
        public array|null $hobbies = null,
    )
    {
    }


    public function toArray(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'password' => $this->password,
            'phone' => $this->phone,
            'region_id' => $this->region_id,
            'gender' => $this->gender,
            'country_id' => $this->country_id,
            'neigbourhood_id' => $this->neigbourhood_id,
            'national_id' => $this->national_id,
            'type' => $this->type,
            'hobbies' => $this->hobbies,
        ];
    }

}
