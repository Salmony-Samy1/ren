<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, ensure the parent table's ID is the correct modern type
        Schema::table('restaurant_menu_categories', function (Blueprint $table) {
            $table->bigIncrements('id')->change();
        });

        // Now, create the column with the matching type and add the constraint
        Schema::table('restaurant_menu_items', function (Blueprint $table) {
            // We use unsignedBigInteger to explicitly match bigIncrements
            $table->unsignedBigInteger('restaurant_menu_category_id')->nullable()->after('restaurant_id');

            $table->foreign('restaurant_menu_category_id')
                  ->references('id')
                  ->on('restaurant_menu_categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_menu_items', function (Blueprint $table) {
            $table->dropForeign(['restaurant_menu_category_id']);
            $table->dropColumn('restaurant_menu_category_id');
        });

        Schema::table('restaurant_menu_categories', function (Blueprint $table) {
            // Revert the type change if possible (might require specific DB logic)
            $table->increments('id')->change();
        });
    }
};