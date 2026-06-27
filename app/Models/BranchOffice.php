<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchOffice extends Model
{
    protected $fillable = ['name', 'branch_code', 'address'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_code', 'code');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'branch_office_id');
    }
}
