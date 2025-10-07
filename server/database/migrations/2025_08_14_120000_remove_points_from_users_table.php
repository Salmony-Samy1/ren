<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes safely if they exist (SQLite compatibility)
            if (Schema::hasColumn('users', 'total_points')) {
                try {
                    $table->dropIndex(['total_points']);
                } catch (\Throwable $e) {
                    // ignore if index not present or sqlite limitations
                }
            }

            // Drop columns if exist
            $cols = collect(['points_earned','points_spent','total_points'])
                ->filter(fn($c) => Schema::hasColumn('users', $c))
                ->values()
                ->all();
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('points_earned')->default(0);
            $table->integer('points_spent')->default(0);
            $table->integer('total_points')->default(0);
        });
    }
};