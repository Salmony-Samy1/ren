<?php

namespace App\Repositories\RegionRepo;

use App\Models\Region;
use App\Repositories\BaseRepo;

class RegionRepo extends BaseRepo implements IRegionRepo
{
    public function __construct()
    {
        $this->model = Region::class;
    }
}