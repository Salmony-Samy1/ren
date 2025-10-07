<?php

namespace App\Repositories\PermissionRepo;

use App\Repositories\BaseRepo;
use Spatie\Permission\Models\Permission;

class PermissionRepo extends BaseRepo implements IPermissionRepo
{
    public function __construct()
    {
        $this->model = Permission::class;
    }
}
