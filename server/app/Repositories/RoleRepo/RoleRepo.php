<?php

namespace App\Repositories\RoleRepo;

use App\Repositories\BaseRepo;
use Spatie\Permission\Models\Role;

class RoleRepo extends BaseRepo implements IRoleRepo
{
    public function __construct()
    {
        $this->model = Role::class;
    }
}
