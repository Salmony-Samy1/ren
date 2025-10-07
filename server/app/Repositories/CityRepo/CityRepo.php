<?php

namespace App\Repositories\CityRepo;

use App\Models\City;
use App\Repositories\BaseRepo;

class CityRepo extends BaseRepo implements ICityRepo
{
    public function __construct()
    {
        $this->model = City::class;
    }
}