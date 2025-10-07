<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $user = new User();
        $user->email = 'admin@site.com';
        $user->password = bcrypt('GathroAdmin2025!');
        $user->email_verified_at = now();
        $user->phone = '01010101010';
        $user->country_code = '+971';
        $user->type = 'admin';
        $user->save();

        $user = new User();
        $user->email = 'info@gathro.net';
        $user->password = bcrypt('Gathro#!# 2025@');
        $user->email_verified_at = now();
        $user->phone = '01010101011';
        $user->country_code = '+971';
        $user->type = 'admin';
        $user->save();
    }
}
