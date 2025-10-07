<?php

namespace App\Services;

use App\Repositories\PermissionRepo\IPermissionRepo;
use App\Repositories\RoleRepo\IRoleRepo;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(private readonly IRoleRepo $roleRepo)
    {
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        $result = $this->roleRepo->create($data);
        if($result){
            $result->permissions()->attach($data['permissions']);
            DB::commit();
            return $result;
        }

        DB::rollBack();
        return false;
    }


    public function update(array $data, Role $role){
        DB::beginTransaction();
        $result = $this->roleRepo->update($role->id, $data);
        if($result){
            $role->permissions()->sync($data['permissions']);
            DB::commit();
            return $role;
        }

        DB::rollBack();
        return false;
    }
}
