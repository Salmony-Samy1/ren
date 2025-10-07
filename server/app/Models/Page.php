<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'title', 'content', 'status', 
        'page_type', 'target_audience', 'is_published', 
        'meta_data', 'sort_order'
    ];

    protected $casts = [
        'meta_data' => 'array',
        'is_published' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Scope للصفحات المنشورة
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope حسب نوع الصفحة
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('page_type', $type);
    }

    /**
     * Scope حسب الجمهور المستهدف
     */
    public function scopeForAudience(Builder $query, string $audience): Builder
    {
        return $query->where(function($q) use ($audience) {
            $q->where('target_audience', $audience)
              ->orWhere('target_audience', 'all');
        });
    }

    /**
     * Scope لحفظ الترتيب
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    /**
     * التعامل مع النسخة القديمة من status
     */
    public function scopeOldStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}

