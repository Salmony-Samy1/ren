<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_table_types', function (Blueprint $table) {
            $table->json('conditions')->nullable()->after('specs');
            $table->json('amenities')->nullable()->after('conditions');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_table_types', function (Blueprint $table) {
            $table->dropColumn(['conditions','amenities']);
        });
    }
};

