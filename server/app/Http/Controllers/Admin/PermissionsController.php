<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RBAC\AssignPermissionsRequest;
use App\Http\Resources\PermissionResource;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsController extends Controller
{
    private function authorizeAdmin(): void
    {
        $u = auth('api')->user();
        abort_unless($u && $u->type === 'admin', 403);
    }

    // GET /permissions
    public function index()
    {
        $this->authorizeAdmin();
        $perms = Permission::orderBy('name')->get();
        return format_response(true, __('Fetched successfully'), PermissionResource::collection($perms));
    }

    // POST /roles/{role}/permissions
    public function assignToRole(Role $role, AssignPermissionsRequest $request)
    {
        $this->authorizeAdmin();
        $ids = $request->validated()['permissions'];
        $role->syncPermissions($ids);
        return format_response(true, __('Updated'), new \App\Http\Resources\RoleResource($role->load('permissions')));
    }

    // POST /users/{user}/permissions
    public function assignToUser(User $user, AssignPermissionsRequest $request)
    {
        $this->authorizeAdmin();
        $ids = $request->validated()['permissions'];
        $perms = Permission::whereIn('id', $ids)->pluck('name')->all();
        $user->syncPermissions($perms);
        return format_response(true, __('Updated'), new \App\Http\Resources\UserResource($user->fresh()));
    }
}

