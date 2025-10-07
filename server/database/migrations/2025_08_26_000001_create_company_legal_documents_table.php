<?php

use App\Enums\CompanyLegalDocType;
use App\Enums\ReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_legal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();
            $table->foreignId('main_service_id')->constrained('main_services')->cascadeOnDelete();
            $table->string('doc_type'); // enum in app level
            $table->string('file_path');
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default(ReviewStatus::PENDING->value);
            $table->string('review_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_profile_id', 'main_service_id']);
            $table->index(['main_service_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_legal_documents');
    }
};

