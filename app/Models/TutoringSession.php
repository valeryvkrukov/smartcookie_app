<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tutor_id', 'student_id', 'subject', 'date', 'start_time', 
    'duration', 'location', 'is_initial', 'recurs_weekly', 'status', 'recurring_id',
])]
class TutoringSession extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_initial' => 'boolean',
            'recurs_weekly' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }

    public function scopePendingLog($query)
    {
        return $query->where('date', '<', now()->format('Y-m-d'))
                    ->where('status', 'Scheduled')
                    ->whereNull('tutor_notes');
    }
}
