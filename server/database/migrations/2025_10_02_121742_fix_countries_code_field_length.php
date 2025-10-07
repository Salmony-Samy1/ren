<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحديث طول حقل code إلى 5 أحرف
        DB::statement('ALTER TABLE countries MODIFY COLUMN code VARCHAR(5) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع الطول الأصلي
        DB::statement('ALTER TABLE countries MODIFY COLUMN code VARCHAR(3) NULL');
    }
};
