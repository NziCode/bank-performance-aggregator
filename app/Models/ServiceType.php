<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    protected $fillable = ['name'];

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class, 'service_type_id');
    }

    public function requiresPosFields(): bool
    {
        return $this->name === 'پایش پایانه فروش';
    }
}
