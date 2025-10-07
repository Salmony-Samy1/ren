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
        Schema::table('alerts', function (Blueprint $table) {
            // This adds the 'user_id' column and links it to the 'users' table
            $table->foreignId('user_id')
                  ->after('id') // Optional: places the column after the 'id' column
                  ->constrained()
                  ->onDelete('cascade'); // This will delete a user's alerts if the user is deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            // This removes the link and the column if you ever need to undo the migration
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};