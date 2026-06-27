<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'integer';

    protected $fillable = ['code', 'name', 'zone_code', 'grade', 'address'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_code', 'code');
    }

    public function offices(): HasMany
    {
        return $this->hasMany(BranchOffice::class, 'branch_code', 'code');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'branch_code', 'code');
    }

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class, 'branch_code', 'code');
    }

    public function isPremium(): bool
    {
        return in_array($this->grade, ['ممتاز الف', 'ممتاز ب']);
    }
}
