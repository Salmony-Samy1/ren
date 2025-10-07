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
        Schema::create('exported_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_name');
            $table->enum('report_type', [
                'monthly_revenue', 'detailed_expenses', 'profit_loss', 
                'comprehensive_financial', 'tax_report', 'commissions'
            ]);
            $table->enum('format', ['pdf', 'excel', 'csv']);
            $table->string('file_path')->nullable();
            $table->string('file_size')->nullable();
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->json('filters')->nullable(); // تحفظ الفلاتر المستخدمة
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['requested_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exported_reports');
    }
};
