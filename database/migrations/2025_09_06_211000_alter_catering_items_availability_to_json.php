<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('catering_items') && Schema::hasColumn('catering_items', 'availability_schedule')) {
            // Convert existing varchar data to JSON-safe text before altering type
            $driver = DB::getDriverName();
            Schema::table('catering_items', function (Blueprint $table) use ($driver) {
                // Change to JSON if supported, otherwise to TEXT
                if ($driver === 'mysql') {
                    $table->json('availability_schedule')->nullable()->change();
                } else {
                    $table->text('availability_schedule')->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('catering_items') && Schema::hasColumn('catering_items', 'availability_schedule')) {
            Schema::table('catering_items', function (Blueprint $table) {
                $table->string('availability_schedule')->nullable()->change();
            });
        }
    }
};

