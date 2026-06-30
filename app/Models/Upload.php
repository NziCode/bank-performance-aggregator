<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    protected $fillable = [
        'original_filename',
        'stored_path',
        'period',
        'status',
        'rows_processed',
        'rows_rejected',
        'errors',
        'failure_reason',
        'uploaded_by',
        'processed_at',
    ];

    protected $casts = [
        'errors'       => 'array',
        'period'       => 'date',
        'processed_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'personnel_code');
    }

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class, 'upload_id');
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(int $processed, int $rejected, array $errors = []): void
    {
        $this->update([
            'status'         => 'completed',
            'rows_processed' => $processed,
            'rows_rejected'  => $rejected,
            'errors'         => $errors,
            'processed_at'   => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status'         => 'failed',
            'failure_reason' => $reason,
            'processed_at'   => now(),
        ]);
    }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopeFailed($query) { return $query->where('status', 'failed'); }
}
