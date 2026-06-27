<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RejectionReason extends Model
{
    protected $fillable = ['reason'];

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class, 'rejection_reason_id');
    }
}
