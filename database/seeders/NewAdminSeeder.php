<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class NewAdminSeeder extends Seeder
{
    public function run()
    {
        $user = new User();
        $user->email = 'admin@gathro.com';
        $user->password = bcrypt('123456789');
        $user->email_verified_at = now();
        $user->phone = '01010101212';
        $user->country_code = '+971';
        $user->type = 'admin';
        $user->save();
    }
}
