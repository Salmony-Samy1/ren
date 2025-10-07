<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caterings', function (Blueprint $table) {
            if (!Schema::hasColumn('caterings', 'fulfillment_methods')) {
                $table->json('fulfillment_methods')->nullable()->after('images');
            }
        });
    }

    public function down(): void
    {
        Schema::table('caterings', function (Blueprint $table) {
            if (Schema::hasColumn('caterings', 'fulfillment_methods')) {
                $table->dropColumn('fulfillment_methods');
            }
        });
    }
};

