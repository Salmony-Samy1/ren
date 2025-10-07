<?php

namespace Database\Seeders;

use App\Models\CustomerProfile;
use App\Models\Neigbourhood;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $user = new User();
        $user->email = 'customer@site.com';
        $user->password = bcrypt('123456789');
        $user->email_verified_at = now();
        $user->phone = '123456789';
        $user->country_code = '+971';
        $user->type = 'customer';
        $user->save();

        $profile = new CustomerProfile();
        $profile->user_id = $user->id;
        $profile->first_name = 'Test';
        $profile->last_name = 'User';
        $profile->gender = 'male';
        $profile->region_id = Region::first()->id;
        $profile->neigbourhood_id = Neigbourhood::first()->id;
        $profile->save();
    }
}
