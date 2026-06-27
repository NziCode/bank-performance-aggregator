<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffUnit extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'integer';

    protected $fillable = ['code', 'name'];

    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'staff_unit_code', 'code');
    }
}
