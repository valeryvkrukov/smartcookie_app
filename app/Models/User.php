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
use App\Services\PhotoPathResolver;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'first_name', 
    'last_name', 
    'email', 
    'password', 
    'role', 
    'address', 
    'phone', 
    'is_subscribed',
    'parent_id',
    'tutor_id',
    'can_tutor',
    'time_zone',
    'is_self_student',
    'is_inactive'
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
            'is_subscribed' => 'boolean',
            'is_self_student' => 'boolean',
            'is_inactive' => 'boolean',
        ];
    }

    public static function splitName(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return ['', null];
        }

        $parts = preg_split('/\s+/', $name, 2) ?: [];

        return [
            $parts[0] ?? '',
            $parts[1] ?? null,
        ];
    }

    public function getNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));
    }

    public function setNameAttribute(?string $value): void
    {
        [$firstName, $lastName] = self::splitName($value);

        $this->attributes['first_name'] = $firstName;
        $this->attributes['last_name'] = $lastName;
    }

    /**
     * Accessor for the FullName
     */
    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ]))) ?: ($this->email ?? 'Unknown');
    }

    /**
     * Accessor for the resolved profile photo URL.
     * Falls back to a ui-avatars.com generated avatar when no photo is set or the file is missing.
     */
    public function getPhotoUrlAttribute(): string
    {
        $resolved = PhotoPathResolver::resolve($this->tutorProfile?->photo);

        if ($resolved) {
            return $resolved;
        }

        $name = urlencode(trim("{$this->first_name} {$this->last_name}") ?: 'User');

        return "https://ui-avatars.com/api/?name={$name}&background=212120&color=fff";
    }

    // ── Transparent profile accessors ──────────────────────────────────────

    public function getBlurbAttribute(): ?string
    {
        if ($this->role === 'tutor' || $this->can_tutor) {
            return $this->tutorProfile?->blurb;
        }

        return $this->studentProfile?->blurb;
    }

    public function getPhotoAttribute(): ?string
    {
        return $this->tutorProfile?->photo;
    }

    public function getTutoringSubjectAttribute(): ?string
    {
        return $this->tutorProfile?->tutoring_subject;
    }

    public function getStudentGradeAttribute(): ?string
    {
        return $this->studentProfile?->student_grade;
    }

    public function getStudentSchoolAttribute(): ?string
    {
        return $this->studentProfile?->student_school;
    }

    public function getTutoringGoalsAttribute(): ?string
    {
        return $this->studentProfile?->tutoring_goals;
    }

    /**
     * Relation to wallet
     */
    public function credit(): HasOne
    {
        return $this->hasOne(Credit::class, 'user_id');
    }

    /**
     * Relation to tutor profile (blurb, photo, tutoring_subject)
     */
    public function tutorProfile(): HasOne
    {
        return $this->hasOne(TutorProfile::class, 'user_id');
    }

    /**
     * Relation to student profile (grade, school, goals, blurb)
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class, 'user_id');
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
     * Relation for student → tutors (student side of the pivot)
     */
    public function assignedTutors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tutor_student_assignments', 'student_id', 'tutor_id');
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
