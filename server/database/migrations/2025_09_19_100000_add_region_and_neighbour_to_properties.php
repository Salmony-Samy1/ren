<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'city_id')) {
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            }
            if (!Schema::hasColumn('properties', 'region_id')) {
                $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            }
            if (!Schema::hasColumn('properties', 'neigbourhood_id')) {
                $table->foreignId('neigbourhood_id')->nullable()->constrained('neigbourhoods')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'neigbourhood_id')) {
                $table->dropConstrainedForeignId('neigbourhood_id');
            }
            if (Schema::hasColumn('properties', 'region_id')) {
                $table->dropConstrainedForeignId('region_id');
            }
            if (Schema::hasColumn('properties', 'city_id')) {
                $table->dropConstrainedForeignId('city_id');
            }
        });
    }
};

