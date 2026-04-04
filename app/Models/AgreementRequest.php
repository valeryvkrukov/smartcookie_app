<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'agreement_id', 
    'user_id', 
    'status', 
    'signed_full_name', 
    'signed_date_manual', 
    'signed_at'
])]
class AgreementRequest extends Model
{
    // ── Casts: date casting for Carbon compatibility
    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'signed_date_manual' => 'date',
        ];
    }

    // ── Relation: belongs to the agreement document
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    // ── Relation: belongs to the signatory user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
