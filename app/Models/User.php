<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens;
    protected $primaryKey = 'personnel_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'personnel_code',
        'first_name',
        'last_name',
        'national_code',
        'position',
        'mobile',
        'education',
        'gender',
        'password',
        'workplace_type',
        'branch_code',
        'zone_code',
        'branch_office_id',
        'staff_unit_code',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
    ];

    // full name accessor
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

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

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class, 'personnel_code', 'personnel_code');
    }

    public function validatedPerformances(): HasMany
    {
        return $this->hasMany(Performance::class, 'validated_by', 'personnel_code');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // بعداً محدود میکنیم
    }

    public function getFilamentName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
