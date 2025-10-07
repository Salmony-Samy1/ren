<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('phone')->unique();
            $table->string('country_code');
            $table->enum('type', ['customer', 'admin', 'provider']);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name');
            $table->dropColumn('phone');
            $table->dropColumn('country_code');
            $table->dropColumn('type');
            $table->dropSoftDeletes();
        });
    }
};
