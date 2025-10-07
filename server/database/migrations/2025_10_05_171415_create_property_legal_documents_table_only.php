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
        Schema::create('property_legal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->enum('document_type', ['ownership_contract', 'rental_contract', 'other'])->default('other');
            $table->string('document_name');
            $table->text('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable(); // pdf, jpg, png, etc.
            $table->integer('file_size')->nullable(); // in bytes
            $table->text('description')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_legal_documents');
    }
};