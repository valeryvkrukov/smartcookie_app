<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Student extends User
{
    protected $table = 'users';
    
    protected static function booted(): void
    {
        static::addGlobalScope('role', fn (Builder $builder) => $builder->where('role', 'student'));
        static::addGlobalScope('active', fn (Builder $builder) => $builder->where('is_inactive', false));
    }

    /**
     * Query scope to include inactive students (bypasses the active global scope).
     */
    public function scopeWithInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }
}