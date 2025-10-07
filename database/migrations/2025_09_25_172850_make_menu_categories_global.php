<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_menu_categories', function (Blueprint $table) {
            // 1. Drop the foreign key constraint first
            $table->dropForeign(['restaurant_id']);

            // 2. Now, drop the column
            $table->dropColumn('restaurant_id');
        });
    }

    public function down(): void
    {
        // This makes the migration reversible if you change your mind
        Schema::table('restaurant_menu_categories', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
        });
    }
};