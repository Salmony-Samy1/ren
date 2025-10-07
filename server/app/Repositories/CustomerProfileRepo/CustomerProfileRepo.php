<?php

namespace App\Repositories\CustomerProfileRepo;

use App\Models\CustomerProfile;
use App\Repositories\BaseRepo;

class CustomerProfileRepo extends BaseRepo implements ICustomerProfileRepo
{
    public function __construct()
    {
        $this->model = CustomerProfile::class;
    }
}