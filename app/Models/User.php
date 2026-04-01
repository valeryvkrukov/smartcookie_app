<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'first_name', 
    'last_name', 
    'email', 
    'password', 
    'role', 
    'address', 
    'phone', 
    'is_admin', 
    'is_subscribed',
    'parent_id',
    'tutor_id',
    'student_grade',
    'student_school',
    'tutoring_subject',
    'tutoring_goals',
    'photo',
    'blurb',
    'can_tutor'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_subscribed' => 'boolean',
        ];
    }

    /**
     * Accessor for the FullName
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Relation to wallet
     */
    public function credit(): HasOne
    {
        return $this->hasOne(Credit::class, 'user_id');
    }

    /**
     * Relation for parent
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Relation for childs/students
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    /**
     * Relation for student/tutor
     */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    /**
     * Relation for tutor/students
     */
    public function assignedStudents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tutor_student_assignments', 'tutor_id', 'student_id')
                ->withPivot('hourly_payout');
    }

    // Scope to filter tutors (including admins)
    public function scopeIsTutor($query)
    {
        return $query->where(function($q) {
            $q->where('role', 'tutor')
            ->orWhere('can_tutor', true);
        });
    }

    public function subjectRates()
    {
        return $this->hasMany(SubjectRate::class, 'student_id');
    }
}
