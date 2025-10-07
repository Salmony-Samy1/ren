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
        Schema::table('reviews', function (Blueprint $table) {
            // Add columns needed for catering reviews
            $table->enum('status', ['pending_evaluation', 'completed', 'expired'])->default('pending_evaluation')->after('is_approved');
            $table->boolean('commitment_to_promise')->default(false)->after('status');
            $table->boolean('would_recommend')->default(false)->after('commitment_to_promise');
            $table->json('ratings_breakdown')->nullable()->after('would_recommend');
            $table->json('photo_urls')->nullable()->after('ratings_breakdown');
            $table->text('admin_response')->nullable()->after('photo_urls');
            $table->timestamp('response_date')->nullable()->after('admin_response');
            
            // Add indexes for performance
            $table->index(['status', 'created_at']);
            $table->index('commitment_to_promise');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'commitment_to_promise', 
                'would_recommend',
                'ratings_breakdown',
                'photo_urls',
                'admin_response',
                'response_date'
            ]);
            
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex('commitment_to_promise');
        });
    }
};