<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountriesSaudiBahrainSeeder extends Seeder
{
    public function run(): void
    {
        // Remove any other countries to satisfy "السعودية و البحرين وليس الاثنين الاسعودية"
        // Keep it safe: deactivate others instead of hard delete
        \DB::table('countries')->update(['is_active' => false]);

        // Saudi Arabia
        $sa = Country::firstOrCreate(['id' => 1], ['is_active' => true]);
        if (method_exists($sa, 'translateOrNew')) {
            $sa->translateOrNew('ar')->name = 'المملكة العربية السعودية';
            $sa->translateOrNew('en')->name = 'Saudi Arabia';
            $sa->save();
        }
        $sa->is_active = true; $sa->save();

        // Bahrain
        $bh = Country::firstOrCreate(['id' => 2], ['is_active' => true]);
        if (method_exists($bh, 'translateOrNew')) {
            $bh->translateOrNew('ar')->name = 'البحرين';
            $bh->translateOrNew('en')->name = 'Bahrain';
            $bh->save();
        }
        $bh->is_active = true; $bh->save();

        // Ensure only these two are active
        \DB::table('countries')->whereNotIn('id', [$sa->id, $bh->id])->update(['is_active' => false]);
    }
}

