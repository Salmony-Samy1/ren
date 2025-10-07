<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateRestaurantTableTypesToTables extends Command
{
    protected $signature = 'restaurants:migrate-table-types';
    protected $description = 'Migrate data from restaurant_table_types to restaurant_tables (Normal/VIP model)';

    public function handle(): int
    {
        if (!DB::getSchemaBuilder()->hasTable('restaurant_table_types')) {
            $this->info('restaurant_table_types does not exist. Nothing to migrate.');
            return self::SUCCESS;
        }
        if (!DB::getSchemaBuilder()->hasTable('restaurant_tables')) {
            $this->error('restaurant_tables does not exist. Run migrations first.');
            return self::FAILURE;
        }

        $types = DB::table('restaurant_table_types')->get();
        $migrated = 0;
        foreach ($types as $t) {
            // Map to VIP rows using price_per_table inferred from price_per_person * capacity_people
            $pricePerTable = (float)$t->price_per_person * (int)$t->capacity_people;
            DB::table('restaurant_tables')->insert([
                'restaurant_id' => $t->restaurant_id,
                'name' => $t->name,
                'type' => 'VIP',
                'capacity_people' => (int)$t->capacity_people,
                'price_per_person' => null,
                'price_per_table' => $pricePerTable,
                'quantity' => (int)$t->count,
                're_availability_type' => 'AUTO',
                'auto_re_availability_minutes' => null,
                'conditions' => $t->conditions ?? null,
                'amenities' => $t->amenities ?? null,
                'media' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $migrated++;
        }

        $this->info("Migrated {$migrated} table types to restaurant_tables.");
        return self::SUCCESS;
    }
}

