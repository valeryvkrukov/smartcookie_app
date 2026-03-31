<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Student extends User
{
    protected $table = 'users';
    
    protected static function booted(): void
    {
        static::addGlobalScope('role', fn (Builder $builder) => $builder->where('role', 'student'));
    }

    // Relation via assingments
    public function assignedTutors()
    {
        return $this->belongsToMany(Tutor::class, 'tutor_student_assignments', 'student_id', 'tutor_id');
    }
}