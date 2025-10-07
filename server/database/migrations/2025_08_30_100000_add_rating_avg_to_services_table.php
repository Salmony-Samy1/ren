<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'rating_avg')) {
                $table->decimal('rating_avg', 3, 2)->default(0)->after('price_amount');
                $table->index('rating_avg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'rating_avg')) {
                $table->dropIndex(['rating_avg']);
                $table->dropColumn('rating_avg');
            }
        });
    }
};

