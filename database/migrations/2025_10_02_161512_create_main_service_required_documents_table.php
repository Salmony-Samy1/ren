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
        Schema::create('main_service_required_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_service_id')->constrained('main_services')->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->enum('document_type', ['tourism_license', 'commercial_registration', 'food_safety_cert', 'catering_permit']);
            $table->boolean('is_required')->default(true);
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->timestamps();
            
            // Unique constraint to prevent duplicate requirements
            $table->unique(['main_service_id', 'country_id', 'document_type'], 'msrd_unique');
            
            // Indexes for better performance
            $table->index(['main_service_id', 'country_id'], 'msrd_service_country');
            $table->index(['country_id', 'document_type'], 'msrd_country_doc');
            $table->index(['main_service_id', 'document_type'], 'msrd_service_doc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_service_required_documents');
    }
};