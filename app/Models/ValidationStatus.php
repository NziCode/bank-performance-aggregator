<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValidationStatus extends Model
{
    protected $fillable = ['name'];

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class, 'validation_status_id');
    }
}
