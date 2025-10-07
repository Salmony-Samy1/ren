<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('code', 3)->unique(); // SAR, BHD
                $table->string('name');
                $table->decimal('rate', 12, 6)->default(1); // relative to SAR maybe
                $table->decimal('fee_percent', 5, 2)->default(0); // commission or processing fee percent
                $table->timestamps();
            });

            DB::table('currencies')->insert([
                ['code' => 'SAR', 'name' => 'Saudi Riyal', 'rate' => 1.000000, 'fee_percent' => 0.00, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'BHD', 'name' => 'Bahraini Dinar', 'rate' => 0.100000, 'fee_percent' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'price_currency')) {
                $table->string('price_currency', 3)->nullable();
            }
            if (!Schema::hasColumn('services', 'price_amount')) {
                $table->decimal('price_amount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('services', 'available_from')) {
                $table->date('available_from')->nullable();
            }
            if (!Schema::hasColumn('services', 'available_to')) {
                $table->date('available_to')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'available_to')) { $table->dropColumn('available_to'); }
            if (Schema::hasColumn('services', 'available_from')) { $table->dropColumn('available_from'); }
            if (Schema::hasColumn('services', 'price_amount')) { $table->dropColumn('price_amount'); }
            if (Schema::hasColumn('services', 'price_currency')) { $table->dropColumn('price_currency'); }
        });
        Schema::dropIfExists('currencies');
    }
};

