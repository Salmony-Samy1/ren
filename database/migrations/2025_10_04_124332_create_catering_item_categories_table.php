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
        Schema::create('catering_item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // الأصناف مثل: مشروبات، حلويات، أطباق رئيسية
            $table->string('icon')->nullable(); // أيقونة التصنيف
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0); // لترتيب التصنيفات
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catering_item_categories');
    }
};
