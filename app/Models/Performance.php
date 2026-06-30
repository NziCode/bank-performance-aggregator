<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Performance extends Model
{
    protected $fillable = [
        'date',
        'personnel_code',
        'workplace_type',
        'branch_code',
        'zone_code',
        'branch_office_id',
        'staff_unit_code',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'personnel_code', 'personnel_code');
    }

    // --- محل خدمت snapshot شده در زمان ثبت عملکرد ---

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_code', 'code');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_code', 'code');
    }

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'branch_office_id');
    }

    public function staffUnit(): BelongsTo
    {
        return $this->belongsTo(StaffUnit::class, 'staff_unit_code', 'code');
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

    /**
     * متن نمایشی محل خدمت — بر اساس snapshot ذخیره‌شده در همین رکورد
     * (نه وضعیت فعلی کارمند)، تا گزارش‌های گذشته با جابجایی کارمند تغییر نکنند.
     */
    public function getWorkplaceLabelAttribute(): ?string
    {
        return match ($this->workplace_type) {
            'branch' => $this->branch
                ? "شعبه {$this->branch->name} - {$this->branch->code}"
                : null,

            'branch_office' => $this->branchOffice
                ? "باجه {$this->branchOffice->name} - {$this->branchOffice->branch_code}"
                : null,

            'zone' => $this->zone
                ? "حوزه {$this->zone->name} - {$this->zone->code}"
                : null,

            'staff' => $this->staffUnit
                ? "{$this->staffUnit->name} - {$this->staffUnit->code}"
                : null,

            default => null,
        };
    }
}
