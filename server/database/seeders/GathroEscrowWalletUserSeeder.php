<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class GathroEscrowWalletUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update an internal escrow wallet user
        $user = User::firstOrCreate(
            ['email' => 'escrow@gathro.local'],
            [
                'full_name' => 'Gathro Escrow',
                'phone' => '000000000',
                'country_code' => 'SA',
                'type' => 'provider',
                'email_verified_at' => now(),
                'password' => bcrypt('ChangeMe!123'),
            ]
        );

        // Ensure wallet exists
        if (method_exists($user, 'wallet') && !$user->wallet) {
            $user->createWallet([ 'name' => 'Escrow', 'slug' => 'escrow', 'meta' => ['system' => true] ]);
        }
    }
}

