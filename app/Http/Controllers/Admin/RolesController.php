<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleCollection;
use App\Repositories\RoleRepo\IRoleRepo;
use App\Services\RoleService;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function __construct(private readonly IRoleRepo $roleRepo, private readonly RoleService $roleService)
    {
    }

    public function index()
    {
        $roles = $this->roleRepo->getAll(paginated: true);
        if($roles){
            return new RoleCollection($roles);
        }
        return format_response(false, __('Something Went Wrong'), code: 500);
    }

    public function store(RoleRequest $request)
    {
        $result = $this->roleService->create($request->validated());
        if ($result){
            return format_response(true, __('Role created successfully'));
        }
        return format_response(false, __('Something Went Wrong'), code: 500);
    }

    public function show(Role $role)
    {
        $role = $role->load('permissions');
        return format_response(true, __('Role fetched successfully'), new PermissionResource($role));
    }

    public function update(RoleRequest $request, Role $role)
    {
        $result = $this->roleService->update($request->validated(), $role);
        if ($result){
            return format_response(true, __('Role updated successfully'));
        }
        return format_response(false, __('Something Went Wrong'), code: 500);
    }

    public function destroy(Role $role)
    {
        $result = $this->roleRepo->delete($role->id);
        if ($result){
            return format_response(true, __('Role deleted successfully'));
        }
        return format_response(false, __('Something Went Wrong'), code: 500);
    }
}
