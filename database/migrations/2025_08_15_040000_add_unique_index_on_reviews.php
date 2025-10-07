<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Unique already created in create_reviews_table migration; keep this idempotent/no-op to avoid duplicates
        if (!Schema::hasTable('reviews')) return;
    }

    public function down(): void
    {
        // No-op
    }
};

