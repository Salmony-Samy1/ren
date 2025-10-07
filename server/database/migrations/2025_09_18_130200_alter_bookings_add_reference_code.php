<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'reference_code')) {
                $table->string('reference_code', 50)->nullable()->after('order_id');
                $table->index('reference_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'reference_code')) {
                $table->dropIndex(['reference_code']);
                $table->dropColumn('reference_code');
            }
        });
    }
};

