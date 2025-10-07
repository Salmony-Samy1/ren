<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $user = new User();
        $user->email = 'user@site.com';
        $user->password = bcrypt('123456789');
        $user->email_verified_at = now();
        $user->phone = '123456709';
        $user->country_code = '+971';
        $user->type = 'customer';
        $user->save();
    }
}
