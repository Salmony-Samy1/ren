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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('provider_amount', 10, 2)->default(0)->after('commission_amount');
            $table->decimal('platform_amount', 10, 2)->default(0)->after('provider_amount');
            $table->enum('invoice_type', ['customer', 'provider'])->default('customer')->after('platform_amount');
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending')->after('invoice_type');
            $table->string('payment_method')->nullable()->after('status');
            $table->string('transaction_id')->nullable()->after('payment_method');
            $table->json('commission_breakdown')->nullable()->after('transaction_id');
            $table->timestamp('due_date')->nullable()->after('commission_breakdown');
            $table->timestamp('paid_at')->nullable()->after('due_date');
            $table->timestamp('cancelled_at')->nullable()->after('paid_at');
            $table->text('notes')->nullable()->after('cancelled_at');
            
            $table->index(['provider_amount', 'platform_amount']);
            $table->index(['invoice_type', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['provider_amount', 'platform_amount']);
            $table->dropIndex(['invoice_type', 'status']);
            $table->dropIndex(['created_at']);
            
            $table->dropColumn([
                'provider_amount',
                'platform_amount',
                'invoice_type',
                'status',
                'payment_method',
                'transaction_id',
                'commission_breakdown',
                'due_date',
                'paid_at',
                'cancelled_at',
                'notes'
            ]);
        });
    }
};
