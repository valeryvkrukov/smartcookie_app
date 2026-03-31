<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Tutor extends User
{
    protected $table = 'users';
    
    protected static function booted(): void
    {
        static::addGlobalScope('role', fn (Builder $builder) => $builder->where('role', 'tutor'));
    }
}