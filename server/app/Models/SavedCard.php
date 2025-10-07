<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SavedCard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'customer_id',
        'card_id',
        'last_four',
        'brand',
        'expiry_month',
        'expiry_year',
        'is_default',
        'tap_response'
    ];

    protected $casts = [
        'tap_response' => 'encrypted:array', // تشفير البيانات الحساسة تلقائياً
        'is_default' => 'boolean',
        'expiry_month' => 'integer',
        'expiry_year' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحصول على رقم البطاقة مقنع
     */
    public function getMaskedNumberAttribute(): string
    {
        return '**** **** **** ' . $this->last_four;
    }

    /**
     * الحصول على تاريخ انتهاء الصلاحية
     */
    public function getExpiryDateAttribute(): string
    {
        return sprintf('%02d/%d', $this->expiry_month, $this->expiry_year);
    }

    /**
     * التحقق من انتهاء صلاحية البطاقة
     */
    public function isExpired(): bool
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        
        return $this->expiry_year < $currentYear || 
               ($this->expiry_year == $currentYear && $this->expiry_month < $currentMonth);
    }

    /**
     * تعيين البطاقة كافتراضية
     */
    public function setAsDefault(): void
    {
        DB::transaction(function () {
            // إلغاء الافتراضية من جميع البطاقات الأخرى للمستخدم
            static::where('user_id', $this->user_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);

            // تعيين هذه البطاقة كافتراضية
            $this->update(['is_default' => true]);
        });
    }

    /**
     * Scope للحصول على البطاقات غير المنتهية الصلاحية
     */
    public function scopeValid($query)
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        
        return $query->where(function($q) use ($currentYear, $currentMonth) {
            $q->where('expiry_year', '>', $currentYear)
              ->orWhere(function($subQ) use ($currentYear, $currentMonth) {
                  $subQ->where('expiry_year', '=', $currentYear)
                       ->where('expiry_month', '>=', $currentMonth);
              });
        });
    }

    /**
     * Scope للحصول على البطاقة الافتراضية
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
