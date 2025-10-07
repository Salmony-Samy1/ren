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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('total_points')->default(0)->after('remember_token');
            $table->integer('points_earned')->default(0)->after('total_points');
            $table->integer('points_spent')->default(0)->after('points_earned');
            $table->string('referral_code')->unique()->nullable()->after('points_spent');
            $table->foreignId('referred_by')->nullable()->after('referral_code')->constrained('users')->onDelete('set null');
            
            $table->index('total_points');
            $table->index('referral_code');
            $table->index('referred_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropIndex(['total_points', 'referral_code', 'referred_by']);
            $table->dropColumn(['total_points', 'points_earned', 'points_spent', 'referral_code', 'referred_by']);
        });
    }
};
