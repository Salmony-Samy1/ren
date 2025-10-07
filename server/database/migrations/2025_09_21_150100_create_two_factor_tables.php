<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('enabled')->default(false)->index();
            $table->string('method')->default('totp'); // totp, sms, email
            $table->string('secret')->nullable(); // encrypted TOTP secret
            $table->json('backup_codes')->nullable(); // hashed codes
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('two_factor_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel')->default('totp');
            $table->string('code'); // hashed code for sms/email; raw compare for TOTP on-the-fly
            $table->timestamp('expires_at')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['user_id','channel','expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_challenges');
        Schema::dropIfExists('two_factor_settings');
    }
};

