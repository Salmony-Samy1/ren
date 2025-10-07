<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->string('fee_name');
            $table->enum('fee_type', ['booking', 'service', 'processing', 'penalty', 'referral']);
            $table->enum('account_type', ['percentage', 'fixed_amount']);
            $table->decimal('amount', 8, 2);
            $table->json('applicable_services')->nullable(); // الخدمات المطبقة عليها الرسوم
            $table->decimal('min_amount', 8, 2)->default(0);
            $table->decimal('max_amount', 8, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['status', 'fee_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
