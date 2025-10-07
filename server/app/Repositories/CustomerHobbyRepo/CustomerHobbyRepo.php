<?php

namespace App\Repositories\CustomerHobbyRepo;

use App\Models\CustomerHobby;
use App\Repositories\BaseRepo;

class CustomerHobbyRepo extends BaseRepo implements ICustomerHobbyRepo
{
    public function __construct()
    {
        $this->model = CustomerHobby::class;
    }
}