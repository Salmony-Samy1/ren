<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('recipient_id')->constrained('users');
            $table->enum('type', ['direct', 'package']);
            $table->decimal('amount', 12, 2);
            $table->foreignId('gift_package_id')->nullable()->constrained('gift_packages')->nullOnDelete();
            $table->string('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->index(['sender_id', 'recipient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gifts');
    }
};

