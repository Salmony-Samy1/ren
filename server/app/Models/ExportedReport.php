<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportedReport extends Model
{
    protected $fillable = [
        'report_name',
        'report_type',
        'format',
        'file_path',
        'file_size',
        'status',
        'filters',
        'requested_by',
        'completed_at',
        'error_message',
        'progress_percentage'
    ];

    protected $casts = [
        'filters' => 'array',
        'completed_at' => 'datetime',
        'progress_percentage' => 'integer'
    ];

    /**
     * Get the user who requested the report
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Check if the report is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the report is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the report failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get download URL for the report
     */
    public function getDownloadUrlAttribute(): ?string
    {
        if ($this->isCompleted() && $this->file_path) {
            return route('admin.reports.download', $this->id);
        }
        
        return null;
    }

    /**
     * Scope for completed reports
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for processing reports
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for failed reports
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
