<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed points settings
        $this->call([
            PermissionsSeeder::class,
            AdminSeeder::class,
            AdminRoleAndAssignSeeder::class,

            PointsSettingsSeeder::class,
            CountriesSaudiBahrainSeeder::class,
            ReferralSettingsSeeder::class,
            CommissionSettingsSeeder::class,
            InvoiceSeeder::class,
            InvoiceTestSeeder::class,
            NationalIdVerificationSettingsSeeder::class,
            // GathroDatabaseSeeder::class,
            DefaultCancellationPolicySeeder::class,

            ReferralSettingsSeeder::class,
            CitiesBahrainSaudiSeeder::class,
            ProviderSeeder::class,
            PointsSettingsSeeder::class,
            RegionNeighbourhoodSeeder::class,
            GathroAdminSettingsSeeder::class,
            MainServicesAndCategoriesSeeder::class,
            CitiesBahrainSaudiSeeder::class,
            GathroEscrowWalletUserSeeder::class,
            ProviderOnboardingScenarioSeeder::class,
            DemoPropertiesSeeder::class,
            FacilitiesSeeder::class,
        ]);
    }
}
