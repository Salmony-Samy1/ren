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
        Schema::table('services', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['price_currency_id']);
            
            // Add new foreign key constraint to countries table
            $table->foreign('price_currency_id')->references('id')->on('countries')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['price_currency_id']);
            
            // Restore the original foreign key constraint to currencies table
            $table->foreign('price_currency_id')->references('id')->on('currencies')->onDelete('set null');
        });
    }
};
