<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRoleAndAssignSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure admin role exists for both guards
        $guards = ['api','web'];
        foreach ($guards as $guard) {
            Role::findOrCreate('admin', $guard);
        }

        // Gather all permissions and assign to 'admin' role for api guard
        $allPermissions = Permission::pluck('name')->all();
        $adminRole = Role::findByName('admin', 'api');
        $adminRole->syncPermissions($allPermissions);

        // Attach role to admin user
        $admin = User::where('email', 'admin@site.com')->first();
        if ($admin) {
            $admin->assignRole('admin'); // uses default guard_name on model (api)
            // also ensure direct permissions include all (optional)
            $admin->syncPermissions($allPermissions);
        }
    }
}

