<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('restaurant_menu_items', function (Blueprint $table) {
            // This will drop the column from the table
            $table->dropColumn('media_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurant_menu_items', function (Blueprint $table) {
            // This allows you to roll back the change if needed
            $table->string('media_url')->nullable();
        });
    }
};