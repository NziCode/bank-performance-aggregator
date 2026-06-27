<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Performance extends Model
{
    protected $fillable = [
        'date',
        'branch_code',
        'personnel_code',
        'service_type_id',
        'customer_account',
        'customer_name',
        'terminal_number',
        'colleague_account',
        'notes',
        'upload_id',
        'validation_status_id',
        'rejection_reason_id',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'date'         => 'date',
        'validated_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_code', 'code');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'personnel_code', 'personnel_code');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function validationStatus(): BelongsTo
    {
        return $this->belongsTo(ValidationStatus::class, 'validation_status_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class, 'rejection_reason_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by', 'personnel_code');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('validation_status_id', 1);
    }

    public function scopeApproved($query)
    {
        return $query->where('validation_status_id', 2);
    }

    public function scopeRejected($query)
    {
        return $query->where('validation_status_id', 3);
    }

    public function scopeForBranch($query, int $branchCode)
    {
        return $query->where('branch_code', $branchCode);
    }

    public function scopeForEmployee($query, string $personnelCode)
    {
        return $query->where('personnel_code', $personnelCode);
    }

    public function scopeForPeriod($query, string $from, string $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    public function isPosMonitoring(): bool
    {
        return $this->serviceType->requiresPosFields();
    }
}
