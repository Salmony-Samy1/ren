<?php

namespace App\Repositories\UserRepo;

use App\Models\User;
use App\Repositories\BaseRepo;

class UserRepo extends BaseRepo implements IUserRepo
{
    public function __construct()
    {
        $this->model = User::class;
    }
}