<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('provider_reels', function (Blueprint $table) {
            if (!Schema::hasColumn('provider_reels', 'main_service_id')) {
                $table->unsignedBigInteger('main_service_id')->nullable()->after('user_id');
                $table->foreign('main_service_id')->references('id')->on('main_services')->onDelete('set null');
                $table->index('main_service_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('provider_reels', function (Blueprint $table) {
            if (Schema::hasColumn('provider_reels', 'main_service_id')) {
                $table->dropForeign(['main_service_id']);
                $table->dropColumn('main_service_id');
            }
        });
    }
};

