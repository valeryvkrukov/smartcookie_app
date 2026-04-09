<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tutor_id', 'student_id', 'subject', 'date', 'start_time', 'tutor_rate',
    'duration', 'location', 'is_initial', 'recurs_weekly', 'status', 'series_id',
    'tutor_notes',
])]
class TutoringSession extends Model
{
    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'is_initial'   => 'boolean',
            'recurs_weekly' => 'boolean',
            'duration'     => 'integer',
        ];
    }

    /**
     * Human-readable duration label for display purposes.
     * 30 → '30m', 60 → '1h', 90 → '1.5h', 120 → '2h', etc.
     */
    public function getDurationLabelAttribute(): string
    {
        $hours   = intdiv($this->duration, 60);
        $minutes = $this->duration % 60;

        if ($hours === 0) {
            return "{$minutes}m";
        }
        if ($minutes === 0) {
            return "{$hours}h";
        }
        $decimal = $hours + ($minutes / 60);
        return rtrim(rtrim(number_format($decimal, 1), '0'), '.') . 'h';
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(SessionSeries::class, 'series_id');
    }

    public function scopePendingLog($query)
    {
        return $query->where('date', '<', now()->format('Y-m-d'))
                    ->where('status', 'Scheduled')
                    ->whereNull('tutor_notes');
    }
}
