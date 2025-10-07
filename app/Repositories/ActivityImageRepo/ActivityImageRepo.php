<?php

namespace App\Repositories\ActivityImageRepo;

use App\Models\ActivityImage;
use App\Repositories\BaseRepo;

class ActivityImageRepo extends BaseRepo implements IActivityImageRepo
{
    public function __construct()
    {
        $this->model = ActivityImage::class;
    }
}