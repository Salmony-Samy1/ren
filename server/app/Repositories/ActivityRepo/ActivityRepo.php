<?php

namespace App\Repositories\ActivityRepo;

use App\Models\Activity;
use App\Repositories\BaseRepo;

class ActivityRepo extends BaseRepo implements IActivityRepo
{
    public function __construct()
    {
        $this->model = Activity::class;
    }
}