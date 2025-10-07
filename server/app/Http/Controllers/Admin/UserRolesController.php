<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RBAC\AssignRolesRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRolesController extends Controller
{
    private function authorizeAdmin(): void
    {
        $u = auth('api')->user();
        abort_unless($u && $u->type === 'admin', 403);
    }

    // POST /users/{user}/roles
    public function assign(User $user, AssignRolesRequest $request)
    {
        $this->authorizeAdmin();
        $ids = $request->validated()['roles'];
        $roles = Role::whereIn('id', $ids)->pluck('name')->all();
        $user->syncRoles($roles);
        return format_response(true, __('Updated'), new UserResource($user->fresh()));
    }
}

