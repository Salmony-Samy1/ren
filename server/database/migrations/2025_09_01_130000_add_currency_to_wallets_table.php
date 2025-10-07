<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            if (!Schema::hasColumn('wallets', 'currency')) {
                $table->string('currency', 8)->nullable()->after('decimal_places');
                $table->index('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            if (Schema::hasColumn('wallets', 'currency')) {
                $table->dropIndex(['currency']);
                $table->dropColumn('currency');
            }
        });
    }
};

