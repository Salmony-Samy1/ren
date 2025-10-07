<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\CompanyProfile;
use App\Models\Country;
use App\Models\CustomerProfile;
use App\Models\Neigbourhood;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $user = new User();
        $user->email = 'provider@site.com';
        $user->password = bcrypt('123456789');
        $user->email_verified_at = now();
        $user->phone = '123456709';
        $user->country_code = '+971';
        $user->type = 'provider';
        $user->save();

        $profile = new CompanyProfile();
        $profile->user_id = $user->id;
        $profile->name = 'Test';
        $profile->owner = 'Test';
        $profile->country_id = Country::first()->id;
        $profile->city_id = City::first()->id;
        $profile->national_id = '123456789';
        $profile->save();
    }
}
