<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'tutoring_session_id', 'tutor_id', 'parent_id', 'credits_spent', 'tutor_payout', 'period'
])]
class Timesheet extends Model
{
    // ── Relation: belongs to the tutoring session
    public function student(): BelongsTo
    {
        return $this->belongsTo(TutoringSession::class, 'tutoring_session_id')->withDefault();
    }

    // ── Relation: belongs to the billed party (parent/user)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Static method for conversion of duration into credits
     * "0:30" -> 0.5, "1:00" -> 1.0
     */
    public static function calculateCredits($duration): float
    {
        list($hours, $minutes) = explode(':', $duration);
        return (int)$hours + ((int)$minutes / 60);
    }
}
