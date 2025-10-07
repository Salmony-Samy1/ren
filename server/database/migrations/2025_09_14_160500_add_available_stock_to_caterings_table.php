<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caterings', function (Blueprint $table) {
            if (!Schema::hasColumn('caterings', 'available_stock')) {
                $table->integer('available_stock')->default(0)->after('images');
            }
        });
    }

    public function down(): void
    {
        Schema::table('caterings', function (Blueprint $table) {
            if (Schema::hasColumn('caterings', 'available_stock')) {
                $table->dropColumn('available_stock');
            }
        });
    }
};

