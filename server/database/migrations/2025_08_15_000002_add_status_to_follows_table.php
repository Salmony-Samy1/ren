<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('follows', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->after('user_id');
            $table->unique(['user_id', 'follower_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::table('follows', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropUnique(['user_id', 'follower_id']);
            $table->dropColumn('status');
        });
    }
};

