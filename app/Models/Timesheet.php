<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'tutoring_session_id', 'tutor_id', 'billed_user_id', 'credits_spent', 'tutor_payout'
])]
class Timesheet extends Model
{
    // ── Relation: belongs to the tutoring session
    public function student(): BelongsTo
    {
        return $this->belongsTo(TutoringSession::class, 'tutoring_session_id')->withDefault();
    }

    // ── Relation: belongs to the billed party (parent/user)
    public function billedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'billed_user_id');
    }

    // ── Computed: billing period derived from creation date
    public function getPeriodAttribute(): string
    {
        return $this->created_at && $this->created_at->day <= 15 ? '1-15' : '16-end';
    }

    /**
     * Calculate credits from duration stored as integer minutes.
     * 30 → 0.5, 60 → 1.0, 90 → 1.5, 120 → 2.0
     */
    public static function calculateCredits(int $duration): float
    {
        return $duration / 60;
    }
}
