<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Make booking_id nullable to allow order-level invoices
            $table->foreignId('booking_id')->nullable()->change();

            // Add invoice_number for human-friendly reference
            if (!Schema::hasColumn('invoices', 'invoice_number')) {
                $table->string('invoice_number', 50)->nullable()->after('id');
                $table->unique('invoice_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'invoice_number')) {
                $table->dropUnique(['invoice_number']);
                $table->dropColumn('invoice_number');
            }
            // Revert booking_id to not nullable only if safe in your environment
            // $table->foreignId('booking_id')->nullable(false)->change();
        });
    }
};

