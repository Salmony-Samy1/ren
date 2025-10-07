<?php

namespace App\Repositories\FollowRepo;

use App\Models\Follow;
use App\Repositories\BaseRepo;

class FollowRepo extends BaseRepo implements IFollowRepo
{
    public function __construct()
    {
        $this->model = Follow::class;
    }
}