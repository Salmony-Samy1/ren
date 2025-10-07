<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // New fields
            $table->string('street')->nullable()->after('address');
            $table->string('neighborhood')->nullable()->after('street');
            $table->string('region')->nullable()->after('neighborhood');

            // Change type to enum-like using check constraint or limited varchar (portable)
            // If database supports enum, you may alter to enum; here we keep string with validation at app layer
            // Optional: add index for type if used for filtering
            $table->string('type', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn(['street','neighborhood','region']);
            // Can't easily revert altered column length in a portable way; leaving as-is
        });
    }
};

