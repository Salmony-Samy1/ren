<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gifts', function (Blueprint $table) {
            if (!Schema::hasColumn('gifts', 'service_id')) {
                $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete()->after('gift_package_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gifts', function (Blueprint $table) {
            if (Schema::hasColumn('gifts', 'service_id')) {
                $table->dropConstrainedForeignId('service_id');
            }
        });
    }
};

