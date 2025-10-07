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
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->enum('rule_type', ['service_type', 'volume_based', 'rating_based', 'referral_based']);
            $table->enum('commission_type', ['percentage', 'fixed_amount']);
            $table->decimal('commission_value', 8, 2);
            $table->json('rule_parameters')->nullable(); // مثل thresholds, service_types, etc.
            $table->decimal('min_commission', 8, 2)->default(0);
            $table->decimal('max_commission', 8, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('priority')->default(0); // ترتيب الأولوية في التطبيق
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['status', 'rule_type']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
